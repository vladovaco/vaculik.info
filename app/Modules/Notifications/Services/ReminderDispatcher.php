<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use App\Models\UserModel;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Household\Models\HouseholdModel;
use Modules\Household\Models\PersonModel;

/**
 * Runs from cron (`php spark app:tick`). Turns deadlines and today's events into
 * notifications: reminders N days before each deadline and a morning digest.
 */
final class ReminderDispatcher
{
    private Notifier $notifier;

    public function __construct(?Notifier $notifier = null)
    {
        $this->notifier = $notifier ?? new Notifier();
    }

    /**
     * @return int Number of notifications created.
     */
    public function run(?Time $now = null): int
    {
        $now     = $now ?? Time::now();
        $created = 0;

        foreach (model(HouseholdModel::class)->findAll() as $household) {
            $users = $this->adultUsers($household->id);
            if ($users === []) {
                continue;
            }
            $created += $this->remindDeadlines($household->id, $users, $now);
            if ((int) $now->getHour() >= config('Family')->digestHour) {
                $created += $this->digest($household->id, $users, $now);
            }
        }

        return $created;
    }

    /**
     * @param list<User> $users
     */
    private function remindDeadlines(int $householdId, array $users, Time $now): int
    {
        $today   = $now->setTime(0, 0);
        $offsets = config('Family')->reminderOffsets;
        $max     = max($offsets);
        $created = 0;

        foreach (service('deadlines')->between($householdId, $today->subDays(1), $today->addDays($max)) as $deadline) {
            $days = $deadline->daysLeft($today);
            if ($days < 0) {
                $offset = -1; // one "overdue" reminder the day after
            } elseif (in_array($days, $offsets, true)) {
                $offset = $days;
            } else {
                continue;
            }

            $title = match (true) {
                $offset < 0  => 'Po termíne: ' . $deadline->title,
                $offset === 0 => 'Dnes: ' . $deadline->title,
                $offset === 1 => 'Zajtra: ' . $deadline->title,
                default      => "O {$offset} dní: " . $deadline->title,
            };
            $level = $offset < 0 ? Notifier::LEVEL_DANGER : ($offset <= 1 ? Notifier::LEVEL_WARNING : Notifier::LEVEL_INFO);

            foreach ($users as $user) {
                if (! $this->canSee($user, $deadline->kind)) {
                    continue;
                }
                if ($this->notifier->notify($user, $householdId, $deadline->ref . ':offset:' . $offset, $title, $deadline->detail . ' · ' . $deadline->dueAt->format('j.n.Y'), $deadline->url, $level)) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Morning digest: today's events and deadlines in one message.
     *
     * @param list<User> $users
     */
    private function digest(int $householdId, array $users, Time $now): int
    {
        $today  = $now->setTime(0, 0);
        $events = model(CalendarEventModel::class)->between($householdId, $today, $today->addDays(1));
        $dead   = service('deadlines')->between($householdId, $today, $today);
        if ($events === [] && $dead === []) {
            return 0;
        }

        $persons = [];
        foreach (model(PersonModel::class)->forHousehold($householdId) as $p) {
            $persons[$p->id] = $p->displayName();
        }

        $lines = [];
        foreach ($events as $e) {
            $who     = $e->person_id && isset($persons[$e->person_id]) ? $persons[$e->person_id] . ': ' : '';
            $lines[] = $e->timeLabel() . ' ' . $who . $e->title;
        }
        foreach ($dead as $d) {
            $lines[] = '⚠ ' . $d->title;
        }

        $created = 0;
        $title   = 'Dnes: ' . count($events) . ' ' . $this->plural(count($events), 'udalosť', 'udalosti', 'udalostí') . ($dead !== [] ? ', ' . count($dead) . ' ' . $this->plural(count($dead), 'termín', 'termíny', 'termínov') : '');
        foreach ($users as $user) {
            if ($this->notifier->notify($user, $householdId, 'digest:' . $today->format('Y-m-d'), $title, implode("\n", array_slice($lines, 0, 8)), site_url('/'), Notifier::LEVEL_INFO)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return list<User>
     */
    private function adultUsers(int $householdId): array
    {
        $personIds = array_map(static fn ($p) => $p->id, model(PersonModel::class)->forHousehold($householdId));
        if ($personIds === []) {
            return [];
        }
        $users = model(UserModel::class)->whereIn('person_id', $personIds)->where('active', 1)->findAll();

        return array_values(array_filter($users, static fn (User $u) => $u->inGroup('admin', 'adult')));
    }

    private function canSee(User $user, string $kind): bool
    {
        return match ($kind) {
            'payment'  => $user->can('finance.manage'),
            'document' => $user->can('documents.view'),
            default    => true,
        };
    }

    private function plural(int $n, string $one, string $few, string $many): string
    {
        return $n === 1 ? $one : ($n >= 2 && $n <= 4 ? $few : $many);
    }
}
