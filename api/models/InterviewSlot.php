<?php
/**
 * InterviewSlot Model
 */
class InterviewSlot {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getByUserId(int $userId): array {
        $stmt = self::db()->prepare('SELECT * FROM interview_slots WHERE booked_by = ? ORDER BY start_time DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getAvailableSlots(): array {
        $stmt = self::db()->prepare('SELECT * FROM interview_slots WHERE status = "open" AND start_time > NOW() ORDER BY start_time ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function book(int $slotId, int $userId): bool {
        $stmt = self::db()->prepare('UPDATE interview_slots SET booked_by = ?, status = "booked" WHERE id = ? AND status = "open"');
        return $stmt->execute([$userId, $slotId]);
    }

    public static function create(array $data): bool {
        $stmt = self::db()->prepare('INSERT INTO interview_slots (recruiter_id, start_time, end_time, status) VALUES (?, ?, ?, ?)');
        return $stmt->execute([
            $data['recruiter_id'],
            $data['start_time'],
            $data['end_time'],
            $data['status'] ?? 'open'
        ]);
    }
}
