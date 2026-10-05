<?php

declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use Modules\Core\Contracts\DashboardCardProvider;
use Modules\Core\Dashboard\Card;

class DashboardController extends BaseController
{
    /**
     * "Dnes": one screen that aggregates cards from every module.
     */
    public function index(): string
    {
        $user  = auth()->user();
        $today = Time::now();
        $cards = [];

        foreach (config('Family')->dashboardProviders as $class) {
            $provider = new $class();
            if ($provider instanceof DashboardCardProvider) {
                array_push($cards, ...$provider->dashboardCards($user, $today));
            }
        }

        return view('Modules\Dashboard\Views\index', [
            'title'  => 'Dnes',
            'today'  => $today,
            'cards'  => Card::sort($cards),
            'person' => service('householdContext')->person(),
        ]);
    }

    /**
     * "Viac": grid of all modules.
     */
    public function more(): string
    {
        return view('Modules\Dashboard\Views\more', ['title' => 'Viac']);
    }

    public function comingSoon(string $name): string
    {
        return view('Modules\Dashboard\Views\coming_soon', ['title' => urldecode($name)]);
    }
}
