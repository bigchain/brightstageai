<?php
/**
 * Presentation database operations.
 */

class PresentationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = get_db();
    }

    public function find_by_id(int $id, int $user_id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM presentations WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch() ?: null;
    }

    public function find_by_id_admin(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM presentations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function list_by_user(int $user_id, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, COUNT(s.id) as slide_count
             FROM presentations p
             LEFT JOIN slides s ON s.presentation_id = p.id
             WHERE p.user_id = ?
             GROUP BY p.id
             ORDER BY p.updated_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count_by_user(int $user_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM presentations WHERE user_id = ?');
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }

    public function create(int $user_id, string $title, string $topic, string $audience, int $duration_minutes, string $tone): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO presentations (user_id, title, topic, audience, duration_minutes, tone, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, "draft", NOW(), NOW())'
        );
        $stmt->execute([$user_id, $title, $topic, $audience, $duration_minutes, $tone]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $user_id, array $data): bool
    {
        $allowed = ['title', 'topic', 'audience', 'duration_minutes', 'tone', 'template_id', 'status'];
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
        $values[] = $user_id;

        $sql = 'UPDATE presentations SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount() > 0;
    }

    public function update_status(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE presentations SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function delete(int $id, int $user_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM presentations WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        return $stmt->rowCount() > 0;
    }
}
