<?php

declare(strict_types=1);

namespace Modules\Calendar\Services;

use CodeIgniter\I18n\Time;
use Modules\Calendar\Entities\CalendarSource;
use Modules\Calendar\Models\CalendarEventModel;
use Modules\Calendar\Models\CalendarSourceModel;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;

/**
 * One-way import of an iCalendar feed (Google Calendar "secret address in iCal format",
 * school calendars, ...). Recurring events are expanded inside a sliding window.
 */
final class IcsSync
{
    public const WINDOW_PAST_DAYS   = 30;
    public const WINDOW_FUTURE_DAYS = 400;

    /**
     * @return int Number of imported event instances.
     */
    public function sync(CalendarSource $source, ?string $ics = null): int
    {
        $sources = model(CalendarSourceModel::class);

        try {
            $ics ??= $this->fetch($source->httpsUrl());
            $rows  = $this->parse($source, $ics);
            $count = model(CalendarEventModel::class)->replaceSourceWindow($source->id, $this->windowStart(), $this->windowEnd(), $rows);
            $sources->update($source->id, ['last_synced_at' => Time::now()->format('Y-m-d H:i:s'), 'last_error' => null]);

            return $count;
        } catch (\Throwable $e) {
            $sources->update($source->id, ['last_synced_at' => Time::now()->format('Y-m-d H:i:s'), 'last_error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(CalendarSource $source, string $ics): array
    {
        $calendar = Reader::read($ics, Reader::OPTION_FORGIVING | Reader::OPTION_IGNORE_INVALID_LINES);
        if (! $calendar instanceof VCalendar) {
            throw new \RuntimeException('Súbor nie je platný iCalendar.');
        }

        $timezone = new \DateTimeZone(config('App')->appTimezone);
        $start    = $this->windowStart();
        $end      = $this->windowEnd();
        $expanded = $calendar->expand($start->toDateTime(), $end->toDateTime(), $timezone);
        $rows     = [];

        foreach ($expanded->select('VEVENT') as $event) {
            if (! $event instanceof VEvent || ! isset($event->DTSTART)) {
                continue;
            }
            if (isset($event->STATUS) && strtoupper((string) $event->STATUS) === 'CANCELLED') {
                continue;
            }

            $allDay   = ! $event->DTSTART->hasTime();
            $startsAt = $event->DTSTART->getDateTime($timezone);
            if ($startsAt === null) {
                continue;
            }
            if (isset($event->DTEND)) {
                $endsAt = $event->DTEND->getDateTime($timezone);
            } elseif (isset($event->DURATION)) {
                $endsAt = $startsAt->add(\Sabre\VObject\DateTimeParser::parseDuration((string) $event->DURATION));
            } else {
                $endsAt = $allDay ? $startsAt->modify('+1 day') : $startsAt->modify('+1 hour');
            }
            $startsAt = $startsAt->setTimezone($timezone);
            $endsAt   = ($endsAt ?? $startsAt)->setTimezone($timezone);
            if ($startsAt->getTimestamp() < $start->getTimestamp() || $startsAt->getTimestamp() >= $end->getTimestamp()) {
                continue;
            }

            $rows[] = [
                'household_id' => $source->household_id,
                'source_id'    => $source->id,
                'person_id'    => $source->person_id,
                'uid'          => mb_substr((string) $event->UID . '|' . $startsAt->format('YmdHis'), 0, 255),
                'title'        => mb_substr(trim((string) ($event->SUMMARY ?? '')) ?: '(bez názvu)', 0, 200),
                'description'  => isset($event->DESCRIPTION) ? trim((string) $event->DESCRIPTION) ?: null : null,
                'location'     => isset($event->LOCATION) ? mb_substr(trim((string) $event->LOCATION), 0, 255) ?: null : null,
                'starts_at'    => $startsAt->format('Y-m-d H:i:s'),
                'ends_at'      => $endsAt->format('Y-m-d H:i:s'),
                'all_day'      => $allDay ? 1 : 0,
                'created_at'   => Time::now()->format('Y-m-d H:i:s'),
                'updated_at'   => Time::now()->format('Y-m-d H:i:s'),
            ];
        }

        return $rows;
    }

    public function windowStart(): Time
    {
        return Time::now()->subDays(self::WINDOW_PAST_DAYS)->setTime(0, 0);
    }

    public function windowEnd(): Time
    {
        return Time::now()->addDays(self::WINDOW_FUTURE_DAYS)->setTime(0, 0);
    }

    private function fetch(string $url): string
    {
        $response = service('curlrequest', ['timeout' => 25, 'http_errors' => false])->get($url, ['headers' => ['Accept' => 'text/calendar, */*']]);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Server kalendára odpovedal kódom ' . $response->getStatusCode() . '.');
        }
        $body = (string) $response->getBody();
        if (! str_contains($body, 'BEGIN:VCALENDAR')) {
            throw new \RuntimeException('Odpoveď nie je iCalendar súbor. Skontrolujte, či ide o tajnú adresu vo formáte iCal.');
        }

        return $body;
    }
}
