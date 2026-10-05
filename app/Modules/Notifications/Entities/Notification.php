<?php

declare(strict_types=1);

namespace Modules\Notifications\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int         $id
 * @property int         $household_id
 * @property int         $user_id
 * @property string      $dedupe_key
 * @property string      $title
 * @property string|null $body
 * @property string|null $url
 * @property string      $level
 */
class Notification extends Entity
{
    protected $casts = ['id' => 'integer', 'household_id' => 'integer', 'user_id' => 'integer'];
    protected $dates = ['read_at', 'pushed_at', 'emailed_at', 'created_at'];

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
