<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\I18n\Time;
use Modules\Calendar\Dashboard\CalendarCards;
use Modules\Calendar\Entities\CalendarSource;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Calendar\Models\CalendarSourceModel;
use Modules\Calendar\Services\IcsSync;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class CalendarTest extends FamilyTestCase
{
    private function ics(string $day): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//\r\n"
            . "BEGIN:VEVENT\r\nUID:a1\r\nDTSTART;TZID=Europe/Bratislava:{$day}T150000\r\nDTEND;TZID=Europe/Bratislava:{$day}T160000\r\nSUMMARY:Zubár Ema\r\nLOCATION:Poliklinika\r\nEND:VEVENT\r\n"
            . "BEGIN:VEVENT\r\nUID:b2\r\nDTSTART;VALUE=DATE:{$day}\r\nDTEND;VALUE=DATE:" . Time::parse($day)->addDays(1)->format('Ymd') . "\r\nSUMMARY:Riaditeľské voľno\r\nEND:VEVENT\r\n"
            . "BEGIN:VEVENT\r\nUID:c3\r\nDTSTART;TZID=Europe/Bratislava:{$day}T170000\r\nDTEND;TZID=Europe/Bratislava:{$day}T183000\r\nRRULE:FREQ=WEEKLY;COUNT=3\r\nSUMMARY:Tréning\r\nEND:VEVENT\r\n"
            . "BEGIN:VEVENT\r\nUID:d4\r\nDTSTART;TZID=Europe/Bratislava:{$day}T190000\r\nDTEND;TZID=Europe/Bratislava:{$day}T200000\r\nSTATUS:CANCELLED\r\nSUMMARY:Zrušené\r\nEND:VEVENT\r\n"
            . "END:VCALENDAR\r\n";
    }

    public function testIcsSyncExpandsRecurrenceAndSkipsCancelled(): void
    {
        $this->loginAs('adult');
        $day      = Time::now()->addDays(3)->format('Ymd');
        $sources  = model(CalendarSourceModel::class);
        $sourceId = $sources->insert(['household_id' => $this->householdId, 'name' => 'Test', 'ics_url' => 'https://example.com/a.ics', 'color' => '#ff0000']);
        $source   = $sources->find($sourceId);

        $count = (new IcsSync())->sync($source, $this->ics($day));
        $this->assertSame(5, $count); // 1 timed + 1 all-day + 3 recurrences, cancelled skipped

        $events = model(CalendarEventModel::class)->where('source_id', $sourceId)->orderBy('starts_at')->findAll();
        $this->assertSame('Riaditeľské voľno', $events[0]->title);
        $this->assertTrue($events[0]->all_day);
        $this->assertSame('15:00–16:00', $events[1]->timeLabel());
        $this->assertSame('Poliklinika', $events[1]->location);
        $this->assertNull($sources->find($sourceId)->last_error);

        // Re-sync replaces instead of duplicating.
        (new IcsSync())->sync($source, $this->ics($day));
        $this->assertSame(5, model(CalendarEventModel::class)->where('source_id', $sourceId)->countAllResults());

        $result = $this->get('kalendar?od=' . Time::parse($day)->format('Y-m-d'));
        $result->assertStatus(200);
        $result->assertSee('Zubár Ema');
        $result->assertSee('celý deň');
    }

    public function testInvalidIcsRecordsError(): void
    {
        $sources  = model(CalendarSourceModel::class);
        $sourceId = $sources->insert(['household_id' => $this->householdId, 'name' => 'Zlý', 'ics_url' => 'https://example.com/b.ics', 'color' => '#ff0000']);

        try {
            (new IcsSync())->sync($sources->find($sourceId), 'toto nie je kalendár');
            $this->fail('expected exception');
        } catch (\Throwable) {
        }
        $this->assertNotNull($sources->find($sourceId)->last_error);
    }

    public function testLocalEventCrudAndDashboardCard(): void
    {
        $user     = $this->loginAs('adult');
        $personId = $this->makePerson('Ema', 'child');
        $today    = Time::now()->format('Y-m-d');

        $this->withSession()->post('kalendar', $this->csrf() + [
            'title' => 'Logopéd', 'date' => $today, 'start_time' => '23:00', 'end_date' => $today, 'end_time' => '23:30',
            'person_id' => $personId, 'driver_person_id' => $user->person_id, 'location' => 'Centrum',
        ])->assertRedirect();

        $event = model(CalendarEventModel::class)->first();
        $this->assertSame($today . ' 23:00:00', $event->starts_at->format('Y-m-d H:i:s'));
        $this->assertFalse($event->isExternal());

        $cards = (new CalendarCards())->dashboardCards($user, Time::parse($today . ' 08:00:00'));
        $this->assertNotEmpty($cards);
        $this->assertStringContainsString('Logopéd', $cards[0]->title);
        $this->assertStringContainsString('vezie Adult', $cards[0]->body);

        $this->withSession()->post("kalendar/udalost/{$event->id}/zmazat", $this->csrf())->assertRedirectTo(site_url('kalendar'));
        $this->assertSame(0, model(CalendarEventModel::class)->countAllResults());
    }

    public function testExternalEventCannotBeDeleted(): void
    {
        $this->loginAs('adult');
        $sourceId = model(CalendarSourceModel::class)->insert(['household_id' => $this->householdId, 'name' => 'Ext', 'ics_url' => 'https://example.com/c.ics', 'color' => '#ff0000']);
        $id       = model(CalendarEventModel::class)->insert(['household_id' => $this->householdId, 'source_id' => $sourceId, 'title' => 'Ext', 'starts_at' => '2030-01-01 10:00:00', 'ends_at' => '2030-01-01 11:00:00']);

        $this->withSession()->post("kalendar/udalost/{$id}/zmazat", $this->csrf())->assertSessionHas('error');
        $this->assertSame(1, model(CalendarEventModel::class)->countAllResults());
    }
}
