<?php

declare(strict_types=1);

namespace Modules\Documents\Dashboard;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Modules\Core\Contracts\DashboardCardProvider;
use Modules\Core\Dashboard\Card;
use Modules\Documents\Models\DocumentModel;

final class DocumentCards implements DashboardCardProvider
{
    public function dashboardCards(User $user, Time $today): array
    {
        if (! $user->can('documents.view')) {
            return [];
        }
        $householdId = (int) service('householdContext')->householdId();
        $documents   = model(DocumentModel::class);
        $cards       = [];

        $expired = $documents->expiringBetween($householdId, Time::parse('2000-01-01'), $today->subDays(1));
        if ($expired !== []) {
            $cards[] = new Card(
                title: count($expired) === 1 ? 'Skončila platnosť: ' . $expired[0]->title : count($expired) . ' dokumentom skončila platnosť',
                body: implode(', ', array_slice(array_map(static fn ($d) => $d->title, $expired), 0, 3)),
                icon: 'document',
                url: url_to('documents') . '?expiruje=1',
                urgency: Card::URGENCY_DANGER,
                sortOrder: 30,
            );
        }

        $soon = $documents->expiringBetween($householdId, $today, $today->addDays(30));
        if ($soon !== []) {
            $first = $soon[0];
            $cards[] = new Card(
                title: count($soon) === 1 ? "Do {$first->daysToExpiry($today)} dní končí: {$first->title}" : count($soon) . ' dokumentov končí do 30 dní',
                body: count($soon) === 1 ? sk_date($first->expires_at, 'd. MMMM yyyy') : implode(', ', array_slice(array_map(static fn ($d) => $d->title, $soon), 0, 3)),
                icon: 'document',
                url: url_to('documents') . '?expiruje=1',
                urgency: Card::URGENCY_WARNING,
                sortOrder: 40,
            );
        }

        return $cards;
    }
}
