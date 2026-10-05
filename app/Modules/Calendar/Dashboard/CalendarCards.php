<?php

declare(strict_types=1);

namespace Modules\Calendar\Dashboard;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Core\Contracts\DashboardCardProvider;
use Modules\Core\Dashboard\Card;
use Modules\Household\Models\PersonModel;

final class CalendarCards implements DashboardCardProvider
{
    public function dashboardCards(User $user, Time $today): array
    {
        if (! $user->can('calendar.view')) {
            return [];
        }
        $householdId = (int) service('householdContext')->householdId();
        $events      = model(CalendarEventModel::class);
        $persons     = [];
        foreach (model(PersonModel::class)->forHousehold($householdId) as $p) {
            $persons[$p->id] = $p->displayName();
        }
        $line = static function ($e) use ($persons): string {
            $who = $e->person_id && isset($persons[$e->person_id]) ? $persons[$e->person_id] . ': ' : '';
            $drv = $e->driver_person_id && isset($persons[$e->driver_person_id]) ? ' (vezie ' . $persons[$e->driver_person_id] . ')' : '';

            return $e->timeLabel() . ' ' . $who . $e->title . $drv;
        };

        $dayStart = $today->setTime(0, 0);
        $todays   = $events->between($householdId, $dayStart, $dayStart->addDays(1));
        $cards    = [];

        if ($todays !== []) {
            $upcoming = array_values(array_filter($todays, static fn ($e) => $e->all_day || $e->ends_at->isAfter($today)));
            $list     = $upcoming !== [] ? $upcoming : $todays;
            $cards[]  = new Card(
                title: count($todays) === 1 ? 'Dnes: ' . $todays[0]->title : 'Dnes ' . count($todays) . ' udalostí',
                body: implode("\n", array_map($line, array_slice($list, 0, 4))),
                icon: 'calendar',
                url: url_to('calendar'),
                urgency: Card::URGENCY_INFO,
                sortOrder: 20,
            );
        }

        $tomorrow  = $events->between($householdId, $dayStart->addDays(1), $dayStart->addDays(2));
        if ($tomorrow !== [] && ((int) $today->getHour() >= 17 || $todays === [])) {
            $cards[] = new Card(
                title: count($tomorrow) === 1 ? 'Zajtra: ' . $tomorrow[0]->title : 'Zajtra ' . count($tomorrow) . ' udalostí',
                body: implode("\n", array_map($line, array_slice($tomorrow, 0, 3))),
                icon: 'calendar',
                url: url_to('calendar') . '?od=' . $dayStart->addDays(1)->format('Y-m-d'),
                urgency: Card::URGENCY_INFO,
                sortOrder: 60,
            );
        }

        return $cards;
    }
}
