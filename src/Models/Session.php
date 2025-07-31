<?php
declare(strict_types=1);

namespace App\Models;

class Session extends Base
{
    protected static string $table = 'sessions';
    
    /** @var array<string> */
    protected static array $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'created_at',
        'updated_at'
    ];
    
    /** @var array<string> */
    protected static array $encrypted = [
        'payload'
    ];
    
    /** @var array<string> */
    protected static array $hidden = [];

    /**
     * Create or update a session
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function upsert(array $data): bool
    {
        $data['last_activity'] = date('Y-m-d H:i:s');
        
        $session = $this->findBySessionId($data['session_id']);
        
        if ($session) {
            $existingSession = static::find((int)$session['id']);
            return $existingSession ? $existingSession->update($data) : false;
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $newSession = static::create($data);
        return $newSession->exists();
    }

    /**
     * Find session by session ID
     *
     * @param string $sessionId
     * @return array<string, mixed>|null
     */
    public function findBySessionId(string $sessionId): ?array
    {
        $stmt = static::$pdo->prepare("SELECT * FROM " . static::$table . " WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result && isset($result['payload'])) {
            $result['payload'] = static::$encryptionService->decrypt($result['payload']);
        }

        return $result ?: null;
    }

    /**
     * Clean expired sessions
     *
     * @param int $maxLifetime
     * @return bool
     */
    public function gc(int $maxLifetime): bool
    {
        $stmt = static::$pdo->prepare(
            "DELETE FROM " . static::$table . " WHERE last_activity < ?"
        );
        return $stmt->execute([
            date('Y-m-d H:i:s', time() - $maxLifetime)
        ]);
    }

    /**
     * Find all active sessions for a user
     *
     * @param int $userId
     * @return array<array<string, mixed>>
     */
    public function findActiveUserSessions(int $userId): array
    {
        $stmt = static::$pdo->prepare(
            "SELECT * FROM " . static::$table . " WHERE user_id = ? ORDER BY last_activity DESC"
        );
        $stmt->execute([$userId]);
        $sessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($sessions as &$session) {
            if (isset($session['payload'])) {
                $session['payload'] = static::$encryptionService->decrypt($session['payload']);
            }
        }

        return $sessions;
    }
}