<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use CodeIgniter\Model;
use Modules\Notifications\Entities\Notification;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = Notification::class;
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['household_id', 'user_id', 'dedupe_key', 'title', 'body', 'url', 'level', 'read_at', 'pushed_at', 'emailed_at'];

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('read_at', null)->countAllResults();
    }

    /**
     * @return list<Notification>
     */
    public function recent(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }

    public function exists(int $userId, string $dedupeKey): bool
    {
        return $this->where('user_id', $userId)->where('dedupe_key', $dedupeKey)->countAllResults() > 0;
    }

    public function markAllRead(int $userId): void
    {
        $this->where('user_id', $userId)->where('read_at', null)->set('read_at', date('Y-m-d H:i:s'))->update();
    }
}
