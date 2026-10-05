<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Web Push (VAPID). Generate keys once with `php spark app:vapid` and put them into .env:
 *   push.vapidPublicKey = ...
 *   push.vapidPrivateKey = ...
 *   push.subject = 'mailto:rodina@example.com'
 */
class Push extends BaseConfig
{
    public string $vapidPublicKey  = '';
    public string $vapidPrivateKey = '';
    public string $subject         = 'mailto:rodina@vaculik.info';

    public function enabled(): bool
    {
        return $this->vapidPublicKey !== '' && $this->vapidPrivateKey !== '';
    }
}
