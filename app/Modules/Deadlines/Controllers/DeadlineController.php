<?php

declare(strict_types=1);

namespace Modules\Deadlines\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use Modules\Household\Models\PersonModel;

class DeadlineController extends BaseController
{
    public function index(): string
    {
        $householdId = (int) service('householdContext')->householdId();
        $today       = Time::now()->setTime(0, 0);
        $all         = service('deadlines')->between($householdId, $today->subYears(2), $today->addDays(90));
        $user        = auth()->user();

        // Users without finance rights never see payments, documents need documents.view.
        $all = array_values(array_filter($all, static fn ($d) => match ($d->kind) {
            'payment'  => $user->can('finance.manage'),
            'document' => $user->can('documents.view'),
            default    => true,
        }));

        $groups = ['overdue' => [], 'week' => [], 'month' => [], 'later' => []];
        foreach ($all as $d) {
            $days = $d->daysLeft($today);
            $groups[$days < 0 ? 'overdue' : ($days <= 7 ? 'week' : ($days <= 30 ? 'month' : 'later'))][] = $d;
        }

        $persons = [];
        foreach (model(PersonModel::class)->forHousehold($householdId) as $p) {
            $persons[$p->id] = $p;
        }

        return view('Modules\Deadlines\Views\index', [
            'title'   => 'Termíny',
            'groups'  => $groups,
            'persons' => $persons,
            'today'   => $today,
        ]);
    }
}
