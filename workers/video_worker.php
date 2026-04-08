<?php
/**
 * BrightStage Video Worker
 * Background script that processes queued video jobs.
 * Run via cron every 30 seconds: php /home/convertpods/brightstageai/workers/video_worker.php
 *
 * Pipeline: slide PNGs + audio MP3s → FFmpeg per-slide segments → concat → final MP4
 */

// Bootstrap
require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/functions.php';

// Check exec functions are available (cron/CLI should have them, web PHP does not)
$disabled = explode(',', str_replace(' ', '', ini_get('disable_functions')));
if (in_array('shell_exec', $disabled, true)) {
    error_log('BrightStage Video Worker: shell_exec is disabled. This worker must run via cron/CLI, not web.');
    exit(1);
}

// Check FFmpeg is available
$ffmpeg = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');
if ($ffmpeg === '') {
    $ffmpeg = '/usr/bin/ffmpeg';
    if (!file_exists($ffmpeg)) {
        error_log('BrightStage Video Worker: FFmpeg not found');
        exit(1);
    }
}

// Get the next queued video job
$db = get_db();
$stmt = $db->prepare(
    'SELECT v.*, p.user_id
     FROM videos v
     JOIN presentations p ON p.id = v.presentation_id
     WHERE v.status = "queued"
     ORDER BY v.created_at ASC
     LIMIT 1'
);
$stmt->execute();
$job = $stmt->fetch();

if (!$job) {
    exit(0); // No jobs to process
}

$video_id = $job['id'];
$pres_id = $job['presentation_id'];
$user_id = $job['user_id'];

// Mark as processing
update_video_status($video_id, 'processing', 'Starting video assembly...');

try {
    // Get slides with audio and images
    $stmt = $db->prepare(
        'SELECT * FROM slides WHERE presentation_id = ? ORDER BY slide_order ASC'
    );
    $stmt->execute([$pres_id]);
    $slides = $stmt->fetchAll();

    if (empty($slides)) {
        throw new Exception('No slides found');
    }

    $storage_base = STORAGE_PATH . "/users/{$user_id}/presentations/{$pres_id}";
    $video_dir = $storage_base . '/video';
    if (!is_dir($video_dir)) {
        mkdir($video_dir, 0755, true);
    }

    $segments = [];
    $total_slides = count($slides);

    // Process each slide into a video segment
    foreach ($slides as $i => $slide) {
        $slide_num = $i + 1;
        update_video_status($video_id, 'processing', "Processing slide {$slide_num} of {$total_slides}...");

        // Find image — check multiple possible locations
        $image_path = null;
        $possible_paths = [];

        if (!empty($slide['image_url'])) {
            // Via symlink: public/storage/...
            $possible_paths[] = APP_ROOT . '/public' . $slide['image_url'];
            // Direct storage path (strip /storage/ prefix)
            $possible_paths[] = STORAGE_PATH . '/' . ltrim(str_replace('/storage/', '', $slide['image_url']), '/');
        }
        // Fallback: check by slide order
        $possible_paths[] = $storage_base . "/slides/slide_{$slide['slide_order']}.png";
        $possible_paths[] = $storage_base . "/slides/slide_{$slide['slide_order']}.jpg";

        foreach ($possible_paths as $p) {
            if (file_exists($p)) { $image_path = $p; break; }
        }

        if (!$image_path) {
            error_log("BrightStage Video Worker: No image found for slide {$slide_num}. Checked: " . implode(', ', $possible_paths));
            continue;
        }

        // Get audio path
        // Find audio file
        $audio_path = null;
        $audio_paths = [];
        if (!empty($slide['audio_url'])) {
            $audio_paths[] = APP_ROOT . '/public' . $slide['audio_url'];
            $audio_paths[] = STORAGE_PATH . '/' . ltrim(str_replace('/storage/', '', $slide['audio_url']), '/');
        }
        $audio_paths[] = $storage_base . "/audio/slide_{$slide['slide_order']}.mp3";
        foreach ($audio_paths as $p) {
            if (file_exists($p)) { $audio_path = $p; break; }
        }

        $segment_path = $video_dir . "/segment_{$slide_num}.mp4";

        if (file_exists($audio_path)) {
            // Image + Audio → Video segment
            $cmd = sprintf(
                '%s -y -loop 1 -i %s -i %s -c:v libx264 -tune stillimage -c:a aac -b:a 192k -pix_fmt yuv420p -shortest -movflags +faststart %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($image_path),
                escapeshellarg($audio_path),
                escapeshellarg($segment_path)
            );
        } else {
            // Image only → 5-second silent video segment
            $cmd = sprintf(
                '%s -y -loop 1 -i %s -c:v libx264 -tune stillimage -pix_fmt yuv420p -t 5 -movflags +faststart %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($image_path),
                escapeshellarg($segment_path)
            );
        }

        $output = shell_exec($cmd);

        if (!file_exists($segment_path) || filesize($segment_path) < 100) {
            error_log("BrightStage Video Worker: FFmpeg failed for slide {$slide_num}: {$output}");
            continue;
        }

        $segments[] = $segment_path;
    }

    if (empty($segments)) {
        throw new Exception('No video segments created. Check that slides have rendered images.');
    }

    // Create concat list
    update_video_status($video_id, 'processing', 'Assembling final video...');
    $concat_file = $video_dir . '/concat.txt';
    $concat_content = '';
    foreach ($segments as $seg) {
        $concat_content .= "file '" . $seg . "'\n";
    }
    file_put_contents($concat_file, $concat_content);

    // Concatenate all segments
    $final_path = $video_dir . '/final.mp4';
    $cmd = sprintf(
        '%s -y -f concat -safe 0 -i %s -c copy -movflags +faststart %s 2>&1',
        escapeshellarg($ffmpeg),
        escapeshellarg($concat_file),
        escapeshellarg($final_path)
    );

    $output = shell_exec($cmd);

    if (!file_exists($final_path) || filesize($final_path) < 1000) {
        throw new Exception("FFmpeg concat failed: " . substr($output, 0, 200));
    }

    // Get video duration
    $duration_cmd = sprintf(
        '%s -i %s 2>&1 | grep Duration',
        escapeshellarg($ffmpeg),
        escapeshellarg($final_path)
    );
    $duration_output = shell_exec($duration_cmd);
    $duration_seconds = 0;
    if (preg_match('/Duration:\s*(\d+):(\d+):(\d+)/', $duration_output, $m)) {
        $duration_seconds = ($m[1] * 3600) + ($m[2] * 60) + $m[3];
    }

    // Update video record
    $relative_url = "/storage/users/{$user_id}/presentations/{$pres_id}/video/final.mp4";
    $file_size = filesize($final_path);

    $stmt = $db->prepare(
        'UPDATE videos SET status = "complete", file_url = ?, file_size_bytes = ?, duration_seconds = ?,
         progress_message = "Complete!", updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$relative_url, $file_size, $duration_seconds, $video_id]);

    // Update presentation status
    $stmt = $db->prepare('UPDATE presentations SET status = "video_ready", updated_at = NOW() WHERE id = ?');
    $stmt->execute([$pres_id]);

    // Clean up segment files
    foreach ($segments as $seg) {
        if (file_exists($seg)) unlink($seg);
    }
    if (file_exists($concat_file)) unlink($concat_file);

    error_log("BrightStage Video Worker: Video complete for presentation {$pres_id} ({$duration_seconds}s, " . round($file_size / 1024 / 1024, 1) . "MB)");

} catch (Exception $e) {
    error_log("BrightStage Video Worker: Failed - " . $e->getMessage());
    update_video_status($video_id, 'failed', $e->getMessage());
}

/**
 * Update video job status and progress message.
 */
function update_video_status(int $video_id, string $status, string $message): void
{
    $db = get_db();
    $stmt = $db->prepare(
        'UPDATE videos SET status = ?, progress_message = ?, updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$status, $message, $video_id]);
}
