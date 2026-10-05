<?php

declare(strict_types=1);

namespace Modules\Calendar\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Modules\Calendar\Entities\CalendarEvent;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Calendar\Models\CalendarSourceModel;
use Modules\Household\Models\PersonModel;

class CalendarController extends BaseController
{
    private const AGENDA_DAYS = 14;

    private CalendarEventModel $events;

    public function __construct()
    {
        $this->events = model(CalendarEventModel::class);
    }

    /**
     * Agenda: the next two weeks from a given day, grouped by day.
     */
    public function index(): string
    {
        $from = $this->dateParam('od') ?? Time::now()->setTime(0, 0);
        $to   = $from->addDays(self::AGENDA_DAYS);
        $personId = $this->personParam();

        $byDay = [];
        for ($day = $from; $day->isBefore($to); $day = $day->addDays(1)) {
            $byDay[$day->format('Y-m-d')] = [];
        }
        foreach ($this->events->between($this->householdId(), $from, $to, $personId) as $event) {
            // Multi-day events appear on every day they cover inside the window.
            $cursor = $event->starts_at->setTime(0, 0);
            $last   = ($event->all_day ? $event->ends_at->subMinutes(1) : $event->ends_at)->setTime(0, 0);
            while (! $cursor->isAfter($last)) {
                $key = $cursor->format('Y-m-d');
                if (isset($byDay[$key])) {
                    $byDay[$key][] = $event;
                }
                $cursor = $cursor->addDays(1);
            }
        }

        return view('Modules\Calendar\Views\agenda', [
            'title'    => 'Kalendár',
            'from'     => $from,
            'to'       => $to,
            'byDay'    => $byDay,
            'today'    => Time::now()->setTime(0, 0),
            'persons'  => $this->personsById(),
            'personId' => $personId,
            'sources'  => $this->sourcesById(),
        ]);
    }

    public function month(int $year, int $month): string
    {
        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            throw PageNotFoundException::forPageNotFound();
        }
        $first    = Time::create($year, $month, 1, 0, 0, 0);
        $gridFrom = $first->subDays(((int) $first->format('N')) - 1); // Monday before the 1st
        $gridTo   = $gridFrom->addDays(42);
        $personId = $this->personParam();

        $dots = [];
        foreach ($this->events->between($this->householdId(), $gridFrom, $gridTo, $personId) as $event) {
            $cursor = $event->starts_at->setTime(0, 0);
            $last   = ($event->all_day ? $event->ends_at->subMinutes(1) : $event->ends_at)->setTime(0, 0);
            while (! $cursor->isAfter($last)) {
                $dots[$cursor->format('Y-m-d')][] = $event;
                $cursor = $cursor->addDays(1);
            }
        }

        return view('Modules\Calendar\Views\month', [
            'title'    => sk_date($first, 'LLLL yyyy'),
            'first'    => $first,
            'gridFrom' => $gridFrom,
            'dots'     => $dots,
            'today'    => Time::now()->setTime(0, 0),
            'persons'  => $this->personsById(),
            'personId' => $personId,
            'sources'  => $this->sourcesById(),
        ]);
    }

    public function show(int $id): string
    {
        $event = $this->findOrFail($id);

        return view('Modules\Calendar\Views\show', [
            'title'   => $event->title,
            'event'   => $event,
            'persons' => $this->personsById(),
            'source'  => $event->source_id ? model(CalendarSourceModel::class)->find($event->source_id) : null,
        ]);
    }

    public function new(): string
    {
        $day   = $this->dateParam('den') ?? Time::now()->setTime(0, 0);
        $start = $day->setTime(same_day(Time::now(), $day) ? min(23, (int) Time::now()->getHour() + 1) : 9, 0);

        return $this->form(new CalendarEvent(['starts_at' => $start, 'ends_at' => $start->addHours(1), 'all_day' => false]), url_to('events.create'), 'Nová udalosť');
    }

    public function create(): RedirectResponse
    {
        $data                 = $this->formData();
        $data['household_id'] = $this->householdId();

        $id = $this->events->insert($data);
        if (! $id) {
            return redirect()->back()->withInput()->with('errors', $this->events->errors());
        }

        return redirect()->to(url_to('calendar') . '?od=' . substr((string) $data['starts_at'], 0, 10))->with('message', 'Udalosť bola pridaná.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        $event = $this->findOrFail($id);
        if ($event->isExternal()) {
            return redirect()->route('events.show', [$id])->with('error', 'Udalosti z externého kalendára sa upravujú v ňom.');
        }

        return $this->form($event, url_to('events.update', $id), 'Upraviť udalosť');
    }

    public function update(int $id): RedirectResponse
    {
        $event = $this->findOrFail($id);
        if ($event->isExternal()) {
            return redirect()->route('events.show', [$id])->with('error', 'Udalosti z externého kalendára sa upravujú v ňom.');
        }
        if (! $this->events->update($event->id, $this->formData())) {
            return redirect()->back()->withInput()->with('errors', $this->events->errors());
        }

        return redirect()->route('events.show', [$event->id])->with('message', 'Zmeny boli uložené.');
    }

    public function delete(int $id): RedirectResponse
    {
        $event = $this->findOrFail($id);
        if ($event->isExternal()) {
            return redirect()->route('events.show', [$id])->with('error', 'Udalosti z externého kalendára sa mažú v ňom.');
        }
        $this->events->delete($event->id);

        return redirect()->route('calendar')->with('message', 'Udalosť bola odstránená.');
    }

    private function form(CalendarEvent $event, string $action, string $title): string
    {
        return view('Modules\Calendar\Views\form', [
            'title'   => $title,
            'event'   => $event,
            'action'  => $action,
            'persons' => model(PersonModel::class)->forHousehold($this->householdId()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $post   = $this->request->getPost();
        $allDay = ! empty($post['all_day']);
        $date   = (string) ($post['date'] ?? '');
        $endDate = (string) ($post['end_date'] ?? '') ?: $date;

        if ($allDay) {
            $starts = $date . ' 00:00:00';
            $ends   = (Time::parse($endDate ?: $date)->addDays(1))->format('Y-m-d') . ' 00:00:00';
        } else {
            $starts = $date . ' ' . ((string) ($post['start_time'] ?? '09:00')) . ':00';
            $ends   = $endDate . ' ' . ((string) ($post['end_time'] ?? '10:00')) . ':00';
            if (strtotime($ends) !== false && strtotime($starts) !== false && strtotime($ends) < strtotime($starts)) {
                $ends = $starts;
            }
        }

        return [
            'title'            => trim((string) ($post['title'] ?? '')),
            'person_id'        => ($post['person_id'] ?? '') !== '' ? (int) $post['person_id'] : null,
            'driver_person_id' => ($post['driver_person_id'] ?? '') !== '' ? (int) $post['driver_person_id'] : null,
            'location'         => trim((string) ($post['location'] ?? '')) ?: null,
            'description'      => trim((string) ($post['description'] ?? '')) ?: null,
            'starts_at'        => $starts,
            'ends_at'          => $ends,
            'all_day'          => $allDay ? 1 : 0,
        ];
    }

    private function dateParam(string $name): ?Time
    {
        $value = (string) $this->request->getGet($name);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Time::parse($value)->setTime(0, 0);
        } catch (\Throwable) {
            return null;
        }
    }

    private function personParam(): ?int
    {
        $value = $this->request->getGet('osoba');

        return $value !== null && $value !== '' ? (int) $value : null;
    }

    /**
     * @return array<int, \Modules\Household\Entities\Person>
     */
    private function personsById(): array
    {
        $out = [];
        foreach (model(PersonModel::class)->forHousehold($this->householdId()) as $person) {
            $out[$person->id] = $person;
        }

        return $out;
    }

    /**
     * @return array<int, \Modules\Calendar\Entities\CalendarSource>
     */
    private function sourcesById(): array
    {
        $out = [];
        foreach (model(CalendarSourceModel::class)->forHousehold($this->householdId()) as $source) {
            $out[$source->id] = $source;
        }

        return $out;
    }

    private function householdId(): int
    {
        return (int) service('householdContext')->householdId();
    }

    private function findOrFail(int $id): CalendarEvent
    {
        $event = $this->events->find($id);
        if ($event === null || $event->household_id !== $this->householdId()) {
            throw PageNotFoundException::forPageNotFound('Udalosť neexistuje.');
        }

        return $event;
    }
}
