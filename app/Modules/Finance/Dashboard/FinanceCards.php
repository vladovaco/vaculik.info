<?php

declare(strict_types=1);

namespace Modules\Finance\Dashboard;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Modules\Core\Contracts\DashboardCardProvider;
use Modules\Core\Dashboard\Card;
use Modules\Finance\Models\PaymentModel;

final class FinanceCards implements DashboardCardProvider
{
    public function dashboardCards(User $user, Time $today): array
    {
        if (! $user->can('finance.manage')) {
            return [];
        }
        $householdId = (int) service('householdContext')->householdId();
        $unpaid      = model(PaymentModel::class)->unpaid($householdId);
        $overdue     = array_values(array_filter($unpaid, static fn ($p) => $p->daysLeft($today) < 0));
        $week        = array_values(array_filter($unpaid, static fn ($p) => $p->daysLeft($today) >= 0 && $p->daysLeft($today) <= 7));
        $sum         = static fn (array $list): string => number_format(array_sum(array_map(static fn ($p) => (float) $p->amount, $list)), 2, ',', ' ') . ' €';
        $cards       = [];

        if ($overdue !== []) {
            $cards[] = new Card(
                title: count($overdue) === 1 ? 'Po splatnosti: ' . $overdue[0]->title : count($overdue) . ' platby po splatnosti',
                body: $sum($overdue) . ' · ' . implode(', ', array_slice(array_map(static fn ($p) => $p->title, $overdue), 0, 3)),
                icon: 'wallet',
                url: count($overdue) === 1 ? url_to('payments.show', $overdue[0]->id) : url_to('finance'),
                urgency: Card::URGENCY_DANGER,
                sortOrder: 10,
                actionLabel: count($overdue) === 1 ? 'Zaplatiť cez QR' : 'Otvoriť platby',
            );
        }

        if ($week !== []) {
            $first = $week[0];
            $cards[] = new Card(
                title: count($week) === 1 ? "{$first->title} ({$first->statusLabel($today)})" : count($week) . ' platieb do 7 dní',
                body: $sum($week) . (count($week) > 1 ? ' · najbližšia ' . $first->title . ' ' . $first->statusLabel($today) : ''),
                icon: 'wallet',
                url: count($week) === 1 ? url_to('payments.show', $first->id) : url_to('finance'),
                urgency: Card::URGENCY_WARNING,
                sortOrder: 20,
                actionLabel: count($week) === 1 ? 'Zaplatiť cez QR' : null,
            );
        }

        return $cards;
    }
}
