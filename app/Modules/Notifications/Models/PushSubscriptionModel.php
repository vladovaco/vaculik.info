<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use CodeIgniter\Model;

class PushSubscriptionModel extends Model
{
    protected $table         = 'push_subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'user_agent', 'last_used_at'];

    /**
     * @param array{endpoint: string, keys: array{p256dh: string, auth: string}} $subscription
     */
    public function upsert(int $userId, array $subscription, ?string $userAgent): void
    {
        $hash = sha1($subscription['endpoint']);
        $row  = [
            'user_id'       => $userId,
            'endpoint'      => $subscription['endpoint'],
            'endpoint_hash' => $hash,
            'p256dh'        => $subscription['keys']['p256dh'],
            'auth'          => $subscription['keys']['auth'],
            'user_agent'    => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            'last_used_at'  => date('Y-m-d H:i:s'),
        ];
        $existing = $this->where('endpoint_hash', $hash)->first();
        if ($existing) {
            $this->update($existing['id'], $row);
        } else {
            $this->insert($row);
        }
    }

    public function removeEndpoint(string $endpoint): void
    {
        $this->where('endpoint_hash', sha1($endpoint))->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }
}
