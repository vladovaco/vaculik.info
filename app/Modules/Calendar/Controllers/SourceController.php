<?php

declare(strict_types=1);

namespace Modules\Calendar\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Calendar\Entities\CalendarSource;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Calendar\Models\CalendarSourceModel;
use Modules\Calendar\Services\IcsSync;
use Modules\Household\Models\PersonModel;

class SourceController extends BaseController
{
    private CalendarSourceModel $sources;

    public function __construct()
    {
        $this->sources = model(CalendarSourceModel::class);
    }

    public function index(): string
    {
        return view('Modules\Calendar\Views\sources', [
            'title'   => 'Externé kalendáre',
            'sources' => $this->sources->forHousehold($this->householdId()),
            'persons' => model(PersonModel::class)->forHousehold($this->householdId()),
        ]);
    }

    public function create(): RedirectResponse
    {
        $post = $this->request->getPost();
        $id   = $this->sources->insert([
            'household_id' => $this->householdId(),
            'name'         => trim((string) ($post['name'] ?? '')),
            'ics_url'      => trim((string) ($post['ics_url'] ?? '')),
            'color'        => (string) ($post['color'] ?? '#0ea5e9'),
            'person_id'    => ($post['person_id'] ?? '') !== '' ? (int) $post['person_id'] : null,
            'enabled'      => 1,
        ]);
        if (! $id) {
            return redirect()->back()->withInput()->with('errors', $this->sources->errors());
        }

        return $this->runSync($this->sources->find($id), 'Kalendár bol pridaný');
    }

    public function sync(int $id): RedirectResponse
    {
        return $this->runSync($this->findOrFail($id), 'Synchronizované');
    }

    public function delete(int $id): RedirectResponse
    {
        $source = $this->findOrFail($id);
        model(CalendarEventModel::class)->where('source_id', $source->id)->delete(null, true);
        $this->sources->delete($source->id);

        return redirect()->route('calendar.sources')->with('message', 'Kalendár bol odpojený.');
    }

    private function runSync(CalendarSource $source, string $okPrefix): RedirectResponse
    {
        try {
            $count = (new IcsSync())->sync($source);

            return redirect()->route('calendar.sources')->with('message', "{$okPrefix}: {$count} udalostí.");
        } catch (\Throwable $e) {
            return redirect()->route('calendar.sources')->with('error', 'Synchronizácia zlyhala: ' . $e->getMessage());
        }
    }

    private function householdId(): int
    {
        return (int) service('householdContext')->householdId();
    }

    private function findOrFail(int $id): CalendarSource
    {
        $source = $this->sources->find($id);
        if ($source === null || $source->household_id !== $this->householdId()) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $source;
    }
}
