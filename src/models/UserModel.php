<?php
/**
 * User database operations.
 * All queries use PDO prepared statements.
 */

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = get_db();
    }

    public function find_by_id(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function find_by_email(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $email, string $name, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (email, name, password_hash, credits_balance, plan, role, created_at, updated_at)
             VALUES (?, ?, ?, ?, "free", "user", NOW(), NOW())'
        );
        $stmt->execute([$email, $name, $hash, SIGNUP_BONUS_CREDITS]);
        return (int)$this->db->lastInsertId();
    }

    public function verify_password(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    public function get_credits(int $user_id): int
    {
        $stmt = $this->db->prepare('SELECT credits_balance FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function deduct_credits(int $user_id, int $amount, string $action, ?int $presentation_id = null): bool
    {
        $this->db->beginTransaction();
        try {
            // Check balance
            $balance = $this->get_credits($user_id);
            if ($balance < $amount) {
                $this->db->rollBack();
                return false;
            }

            // Deduct
            $stmt = $this->db->prepare(
                'UPDATE users SET credits_balance = credits_balance - ?, updated_at = NOW() WHERE id = ? AND credits_balance >= ?'
            );
            $stmt->execute([$amount, $user_id, $amount]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            // Log transaction
            $stmt = $this->db->prepare(
                'INSERT INTO credit_transactions (user_id, amount, action, presentation_id, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$user_id, -$amount, $action, $presentation_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function add_credits(int $user_id, int $amount, string $action, ?string $stripe_payment_id = null): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE users SET credits_balance = credits_balance + ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$amount, $user_id]);

            $stmt = $this->db->prepare(
                'INSERT INTO credit_transactions (user_id, amount, action, stripe_payment_id, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$user_id, $amount, $action, $stripe_payment_id]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function email_exists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function list_all(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, name, credits_balance, plan, role, created_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count_all(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
