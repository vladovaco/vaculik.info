<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Modules\Notifications\Models\NotificationModel;
use Modules\Notifications\Models\PushSubscriptionModel;

/**
 * Single entry point for telling a user something: stores an in-app notification
 * (de-duplicated by key), then pushes it to their devices and e-mails it according
 * to their settings.
 */
final class Notifier
{
    public const LEVEL_INFO    = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_DANGER  = 'danger';

    private NotificationModel $notifications;
    private PushSubscriptionModel $subscriptions;

    public function __construct()
    {
        $this->notifications = model(NotificationModel::class);
        $this->subscriptions = model(PushSubscriptionModel::class);
    }

    /**
     * @return bool true when a new notification was created (false = duplicate key)
     */
    public function notify(User $user, int $householdId, string $dedupeKey, string $title, string $body = '', ?string $url = null, string $level = self::LEVEL_INFO): bool
    {
        if ($this->notifications->exists($user->id, $dedupeKey)) {
            return false;
        }

        $id = $this->notifications->insert([
            'household_id' => $householdId,
            'user_id'      => $user->id,
            'dedupe_key'   => mb_substr($dedupeKey, 0, 120),
            'title'        => mb_substr($title, 0, 160),
            'body'         => $body ?: null,
            'url'          => $url,
            'level'        => $level,
        ]);

        if ($this->setting($user, 'push', true)) {
            if ($this->push($user, $title, $body, $url ?? site_url('/'))) {
                $this->notifications->update($id, ['pushed_at' => Time::now()->format('Y-m-d H:i:s')]);
            }
        }
        if ($this->setting($user, 'email', false) && $level !== self::LEVEL_INFO) {
            if ($this->email($user, $title, $body, $url)) {
                $this->notifications->update($id, ['emailed_at' => Time::now()->format('Y-m-d H:i:s')]);
            }
        }

        return true;
    }

    public function setting(User $user, string $key, bool $default): bool
    {
        $value = setting()->get('Notify.' . $key, 'user:' . $user->id);

        return $value === null ? $default : (bool) $value;
    }

    public function saveSetting(User $user, string $key, bool $value): void
    {
        setting()->set('Notify.' . $key, $value ? 1 : 0, 'user:' . $user->id);
    }

    /**
     * Sends a Web Push message to every device of the user. Expired subscriptions are removed.
     *
     * @return bool true when at least one device accepted the message
     */
    public function push(User $user, string $title, string $body, string $url): bool
    {
        $config = config('Push');
        if (! $config->enabled()) {
            return false;
        }
        $devices = $this->subscriptions->forUser($user->id);
        if ($devices === []) {
            return false;
        }

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject'    => $config->subject,
                'publicKey'  => $config->vapidPublicKey,
                'privateKey' => $config->vapidPrivateKey,
            ]], ['TTL' => 6 * 3600]);
            $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url], JSON_UNESCAPED_UNICODE);

            foreach ($devices as $device) {
                $webPush->queueNotification(new Subscription($device['endpoint'], $device['p256dh'], $device['auth']), $payload);
            }

            $ok = false;
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $ok = true;
                } elseif ($report->isSubscriptionExpired()) {
                    $this->subscriptions->removeEndpoint($report->getEndpoint());
                } else {
                    log_message('warning', 'Push failed: {reason}', ['reason' => $report->getReason()]);
                }
            }

            return $ok;
        } catch (\Throwable $e) {
            log_message('error', 'Push error: {error}', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function email(User $user, string $title, string $body, ?string $url): bool
    {
        $address = $user->email;
        if (! $address || config('Email')->fromEmail === '') {
            return false;
        }

        try {
            $email = service('email');
            $email->setTo($address);
            $email->setSubject('[vaculik.info] ' . $title);
            $email->setMessage($body . ($url ? "\n\n" . $url : '') . "\n\n— vaculik.info");
            $email->setMailType('text');

            if (! $email->send(false)) {
                log_message('warning', 'E-mail failed: {debug}', ['debug' => $email->printDebugger(['headers'])]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'E-mail error: {error}', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
