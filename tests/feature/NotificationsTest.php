<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\I18n\Time;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Finance\Models\PaymentModel;
use Modules\Notifications\Models\NotificationModel;
use Modules\Notifications\Services\Notifier;
use Modules\Notifications\Services\ReminderDispatcher;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class NotificationsTest extends FamilyTestCase
{
    public function testNotifyIsDeduplicatedAndShownInBell(): void
    {
        $user     = $this->loginAs('adult');
        $notifier = new Notifier();

        $this->assertTrue($notifier->notify($user, $this->householdId, 'k1', 'Prvé', 'telo', null, Notifier::LEVEL_WARNING));
        $this->assertFalse($notifier->notify($user, $this->householdId, 'k1', 'Prvé znova'));
        $this->assertSame(1, model(NotificationModel::class)->unreadCount($user->id));

        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('1 neprečítaných');

        $result = $this->get('upozornenia');
        $result->assertStatus(200);
        $result->assertSee('Prvé');
        $this->assertSame(0, model(NotificationModel::class)->unreadCount($user->id));
    }

    public function testDispatcherRemindsBeforeDeadlinesAndSendsDigestOnce(): void
    {
        $admin = $this->makeUser('admin');
        $child = $this->makeUser('child');
        $now   = Time::now()->setTime(9, 0);
        $today = $now->format('Y-m-d');

        $payments = model(PaymentModel::class);
        $payments->insert(['household_id' => $this->householdId, 'title' => 'Zajtra splatná', 'amount' => '10.00', 'due_at' => $now->addDays(1)->format('Y-m-d'), 'category' => 'iny', 'recurrence' => 'none']);
        $payments->insert(['household_id' => $this->householdId, 'title' => 'O týždeň', 'amount' => '10.00', 'due_at' => $now->addDays(7)->format('Y-m-d'), 'category' => 'iny', 'recurrence' => 'none']);
        $payments->insert(['household_id' => $this->householdId, 'title' => 'O tri dni', 'amount' => '10.00', 'due_at' => $now->addDays(3)->format('Y-m-d'), 'category' => 'iny', 'recurrence' => 'none']);
        $payments->insert(['household_id' => $this->householdId, 'title' => 'Včera', 'amount' => '10.00', 'due_at' => $now->subDays(1)->format('Y-m-d'), 'category' => 'iny', 'recurrence' => 'none']);
        model(CalendarEventModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Zubár', 'starts_at' => $today . ' 15:00:00', 'ends_at' => $today . ' 16:00:00']);

        $created = (new ReminderDispatcher())->run($now);
        // admin: tomorrow (offset 1) + week (offset 7) + overdue + digest = 4; child gets nothing.
        $this->assertSame(4, $created);
        $this->assertSame(0, model(NotificationModel::class)->unreadCount($child->id));

        $titles = array_map(static fn ($n) => $n->title, model(NotificationModel::class)->recent($admin->id));
        $this->assertContains('Zajtra: Zaplatiť: Zajtra splatná', $titles);
        $this->assertContains('O 7 dní: Zaplatiť: O týždeň', $titles);
        $this->assertContains('Po termíne: Zaplatiť: Včera', $titles);
        $this->assertContains('Dnes: 1 udalosť', $titles);

        // Running again in the same day creates nothing new.
        $this->assertSame(0, (new ReminderDispatcher())->run($now->addHours(2)));
    }

    public function testDigestWaitsUntilMorning(): void
    {
        $this->makeUser('admin');
        $now = Time::now()->setTime(5, 30);
        model(CalendarEventModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Skoro ráno', 'starts_at' => $now->format('Y-m-d') . ' 15:00:00', 'ends_at' => $now->format('Y-m-d') . ' 16:00:00']);

        $this->assertSame(0, (new ReminderDispatcher())->run($now));
        $this->assertSame(1, (new ReminderDispatcher())->run($now->setTime(7, 5)));
    }

    public function testPushSubscriptionEndpointStoresDevice(): void
    {
        $user = $this->loginAs('adult');
        $sub  = ['endpoint' => 'https://push.example.com/abc', 'keys' => ['p256dh' => 'p', 'auth' => 'a']];

        $result = $this->withSession()->withHeaders(['X-CSRF-TOKEN' => csrf_hash()])->withBodyFormat('json')->post('upozornenia/push', $sub);
        $result->assertStatus(200);
        $this->assertCount(1, model(\Modules\Notifications\Models\PushSubscriptionModel::class)->forUser($user->id));

        $this->withSession()->withHeaders(['X-CSRF-TOKEN' => csrf_hash()])->withBodyFormat('json')->post('upozornenia/push', $sub)->assertStatus(200);
        $this->assertCount(1, model(\Modules\Notifications\Models\PushSubscriptionModel::class)->forUser($user->id));
    }
}
