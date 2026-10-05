<?php

declare(strict_types=1);

namespace Modules\Calendar\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * @property int         $id
 * @property int         $household_id
 * @property int|null    $source_id
 * @property int|null    $person_id
 * @property int|null    $driver_person_id
 * @property string|null $uid
 * @property string      $title
 * @property string|null $description
 * @property string|null $location
 * @property Time        $starts_at
 * @property Time        $ends_at
 * @property bool        $all_day
 */
class CalendarEvent extends Entity
{
    protected $casts = [
        'id'               => 'integer',
        'household_id'     => 'integer',
        'source_id'        => '?integer',
        'person_id'        => '?integer',
        'driver_person_id' => '?integer',
        'all_day'          => 'boolean',
    ];

    protected $dates = ['starts_at', 'ends_at', 'created_at', 'updated_at', 'deleted_at'];

    public function isExternal(): bool
    {
        return $this->source_id !== null;
    }

    public function timeLabel(): string
    {
        if ($this->all_day) {
            return 'celý deň';
        }
        $label = $this->starts_at->format('H:i');
        if (! same_day($this->ends_at, $this->starts_at) || $this->ends_at->format('H:i') !== $label) {
            $label .= '–' . $this->ends_at->format('H:i');
        }

        return $label;
    }

    public function isMultiDay(): bool
    {
        // An all-day event ending at 00:00 of the next day is a single day.
        $end = $this->all_day ? $this->ends_at->subMinutes(1) : $this->ends_at;

        return ! same_day($end, $this->starts_at);
    }
}
