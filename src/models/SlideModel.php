<?php
/**
 * Slide database operations.
 */

class SlideModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = get_db();
    }

    public function find_by_id(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM slides WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function list_by_presentation(int $presentation_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM slides WHERE presentation_id = ? ORDER BY slide_order ASC'
        );
        $stmt->execute([$presentation_id]);
        return $stmt->fetchAll();
    }

    public function create(int $presentation_id, int $slide_order, string $title, string $content, string $speaker_notes, string $layout_type = 'bullets'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO slides (presentation_id, slide_order, title, content, speaker_notes, layout_type, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$presentation_id, $slide_order, $title, $content, $speaker_notes, $layout_type]);
        return (int)$this->db->lastInsertId();
    }

    public function create_batch(int $presentation_id, array $slides): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO slides (presentation_id, slide_order, title, content, speaker_notes, layout_type, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        foreach ($slides as $i => $slide) {
            $stmt->execute([
                $presentation_id,
                $slide['slide_order'] ?? $i + 1,
                $slide['title'] ?? '',
                $slide['content'] ?? '',
                $slide['speaker_notes'] ?? '',
                $slide['layout_type'] ?? 'bullets',
            ]);
        }
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'content', 'speaker_notes', 'html_content', 'image_url', 'audio_url', 'layout_type', 'slide_order'];
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[] = "{$key} = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $id;

        $sql = 'UPDATE slides SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount() > 0;
    }

    public function reorder(int $presentation_id, array $slide_ids): void
    {
        $stmt = $this->db->prepare(
            'UPDATE slides SET slide_order = ?, updated_at = NOW() WHERE id = ? AND presentation_id = ?'
        );
        foreach ($slide_ids as $order => $id) {
            $stmt->execute([$order + 1, $id, $presentation_id]);
        }
    }

    public function delete(int $id, int $presentation_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM slides WHERE id = ? AND presentation_id = ?');
        $stmt->execute([$id, $presentation_id]);
        return $stmt->rowCount() > 0;
    }

    public function delete_by_presentation(int $presentation_id): void
    {
        $stmt = $this->db->prepare('DELETE FROM slides WHERE presentation_id = ?');
        $stmt->execute([$presentation_id]);
    }

    public function count_by_presentation(int $presentation_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM slides WHERE presentation_id = ?');
        $stmt->execute([$presentation_id]);
        return (int)$stmt->fetchColumn();
    }
}
