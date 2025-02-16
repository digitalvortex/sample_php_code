<?php
declare(strict_types=1);

namespace App\Models;

class Session extends Base
{
    protected string $table = 'sessions';
    
    protected array $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'created_at',
        'updated_at'
    ];
    
    protected array $encrypted = [
        'payload'
    ];

    /**
     * Create or update a session
     */
    public function upsert(array $data): bool
    {
        $data['last_activity'] = date('Y-m-d H:i:s');
        
        $session = $this->findBySessionId($data['session_id']);
        
        if ($session) {
            return $this->update((int)$session['id'], $data);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }

    /**
     * Find session by session ID
     */
    public function findBySessionId(string $sessionId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result && isset($result['payload'])) {
            $result['payload'] = $this->encryptionService->decrypt($result['payload']);
        }

        return $result ?: null;
    }

    /**
     * Clean expired sessions
     */
    public function gc(int $maxLifetime): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE last_activity < ?"
        );
        return $stmt->execute([
            date('Y-m-d H:i:s', time() - $maxLifetime)
        ]);
    }

    /**
     * Find all active sessions for a user
     */
    public function findActiveUserSessions(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY last_activity DESC"
        );
        $stmt->execute([$userId]);
        $sessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($sessions as &$session) {
            if (isset($session['payload'])) {
                $session['payload'] = $this->encryptionService->decrypt($session['payload']);
            }
        }

        return $sessions;
    }
}