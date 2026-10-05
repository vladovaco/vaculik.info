<?php

declare(strict_types=1);

namespace Modules\Household\Dashboard;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Modules\Core\Contracts\DashboardCardProvider;
use Modules\Core\Dashboard\Card;
use Modules\Household\Models\PersonModel;

/**
 * Phase 0 cards: nudges the admin to set up the household, and shows upcoming birthdays.
 */
final class HouseholdCards implements DashboardCardProvider
{
    public function dashboardCards(User $user, Time $today): array
    {
        $householdId = service('householdContext')->householdId();
        if ($householdId === null) {
            return [];
        }

        $persons = model(PersonModel::class)->forHousehold($householdId);
        $cards   = [];

        if ($persons === [] && $user->can('household.manage')) {
            $cards[] = new Card(
                title: 'Pridajte členov rodiny',
                body: 'Osoby sú základ: rozvrhy, kartičky, úlohy aj platby sa viažu na ne.',
                icon: 'users',
                url: url_to('persons.new'),
                urgency: Card::URGENCY_WARNING,
                sortOrder: 10,
                actionLabel: 'Pridať osobu',
            );
        }

        foreach ($persons as $person) {
            if ($person->birth_date === null) {
                continue;
            }
            $next = $person->birth_date->setDate((int) $today->getYear(), (int) $person->birth_date->getMonth(), (int) $person->birth_date->getDay());
            if ($next->isBefore($today->setTime(0, 0))) {
                $next = $next->addYears(1);
            }
            $days = (int) $today->setTime(0, 0)->difference($next)->getDays();
            if ($days <= 14) {
                $cards[] = new Card(
                    title: $days === 0 ? "Dnes má {$person->displayName()} narodeniny!" : "{$person->displayName()} má narodeniny o {$days} " . ($days === 1 ? 'deň' : ($days < 5 ? 'dni' : 'dní')),
                    body: sk_date($next, 'EEEE d. MMMM'),
                    icon: 'users',
                    url: url_to('persons'),
                    urgency: $days === 0 ? Card::URGENCY_OK : Card::URGENCY_INFO,
                    sortOrder: 50 + $days,
                );
            }
        }

        return $cards;
    }
}
