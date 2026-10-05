<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Modules\Core\Dashboard\Card;

/**
 * A module that wants to show something on the "Dnes" dashboard implements this
 * and registers itself in Config\Family::$dashboardProviders.
 */
interface DashboardCardProvider
{
    /**
     * @return list<Card>
     */
    public function dashboardCards(User $user, Time $today): array;
}
