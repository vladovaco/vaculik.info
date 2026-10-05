<?php

declare(strict_types=1);

namespace Modules\Calendar\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use Modules\Calendar\Entities\CalendarEvent;

class CalendarEventModel extends Model
{
    protected $table          = 'calendar_events';
    protected $primaryKey     = 'id';
    protected $returnType     = CalendarEvent::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'household_id', 'source_id', 'person_id', 'driver_person_id', 'uid', 'title', 'description', 'location',
        'starts_at', 'ends_at', 'all_day',
    ];

    protected $validationRules = [
        'household_id' => 'required|is_natural_no_zero',
        'title'        => 'required|max_length[200]',
        'starts_at'    => 'required|valid_date[Y-m-d H:i:s]',
        'ends_at'      => 'required|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [
        'title'     => ['required' => 'Názov je povinný.'],
        'starts_at' => ['required' => 'Začiatok je povinný.'],
    ];

    /**
     * Events overlapping [$from, $to], ordered for an agenda (all-day first).
     *
     * @return list<CalendarEvent>
     */
    public function between(int $householdId, Time $from, Time $to, ?int $personId = null): array
    {
        $builder = $this->where('household_id', $householdId)
            ->where('starts_at <', $to->format('Y-m-d H:i:s'))
            ->where('ends_at >', $from->format('Y-m-d H:i:s'));
        if ($personId !== null) {
            $builder->groupStart()->where('person_id', $personId)->orWhere('person_id', null)->groupEnd();
        }

        return $builder->orderBy('starts_at')->orderBy('all_day', 'DESC')->findAll();
    }

    /**
     * Replaces every event of a source inside the window (external events are derived data).
     *
     * @param list<array<string, mixed>> $rows
     */
    public function replaceSourceWindow(int $sourceId, Time $from, Time $to, array $rows): int
    {
        $this->db->transStart();
        $this->builder()
            ->where('source_id', $sourceId)
            ->where('starts_at >=', $from->format('Y-m-d H:i:s'))
            ->where('starts_at <', $to->format('Y-m-d H:i:s'))
            ->delete();
        if ($rows !== []) {
            $this->insertBatch($rows);
        }
        $this->db->transComplete();

        return count($rows);
    }
}
