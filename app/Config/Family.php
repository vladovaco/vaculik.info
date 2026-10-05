<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use Modules\Calendar\Dashboard\CalendarCards;
use Modules\Documents\Dashboard\DocumentCards;
use Modules\Documents\Deadlines\DocumentDeadlines;
use Modules\Finance\Dashboard\FinanceCards;
use Modules\Finance\Deadlines\PaymentDeadlines;
use Modules\Household\Dashboard\HouseholdCards;

/**
 * Registry of family modules and the providers they expose to the shared surfaces
 * (dashboard "Dnes", deadlines, assistant tools). Adding a module = adding a line here.
 */
class Family extends BaseConfig
{
    /**
     * Classes implementing Modules\Core\Contracts\DashboardCardProvider.
     *
     * @var list<class-string<\Modules\Core\Contracts\DashboardCardProvider>>
     */
    public array $dashboardProviders = [
        FinanceCards::class,
        CalendarCards::class,
        DocumentCards::class,
        HouseholdCards::class,
    ];

    /**
     * Classes implementing Modules\Core\Contracts\DeadlineProvider.
     *
     * @var list<class-string<\Modules\Core\Contracts\DeadlineProvider>>
     */
    public array $deadlineProviders = [
        PaymentDeadlines::class,
        DocumentDeadlines::class,
    ];

    /**
     * Classes implementing Modules\Core\Contracts\AssistantToolProvider.
     *
     * @var list<class-string<\Modules\Core\Contracts\AssistantToolProvider>>
     */
    public array $assistantToolProviders = [];

    /**
     * Bottom navigation of the mobile shell. Keys are route names.
     *
     * @var list<array{route: string, label: string, icon: string}>
     */
    public array $bottomNav = [
        ['route' => 'dashboard', 'label' => 'Dnes', 'icon' => 'home'],
        ['route' => 'calendar', 'label' => 'Kalendár', 'icon' => 'calendar'],
        ['route' => 'finance', 'label' => 'Peniaze', 'icon' => 'wallet'],
        ['route' => 'documents', 'label' => 'Dokumenty', 'icon' => 'document'],
        ['route' => 'more', 'label' => 'Viac', 'icon' => 'grid'],
    ];

    /**
     * Days before a deadline on which a reminder notification is sent (0 = on the day).
     *
     * @var list<int>
     */
    public array $reminderOffsets = [7, 1, 0];

    /**
     * Hour (household timezone) after which the daily "Dnes" digest is sent.
     */
    public int $digestHour = 7;
}
