<?php

declare(strict_types=1);

namespace Modules\Calendar\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * @property int         $id
 * @property int         $household_id
 * @property int|null    $person_id
 * @property string      $name
 * @property string      $ics_url
 * @property string      $color
 * @property bool        $enabled
 * @property Time|null   $last_synced_at
 * @property string|null $last_error
 */
class CalendarSource extends Entity
{
    protected $casts = [
        'id'           => 'integer',
        'household_id' => 'integer',
        'person_id'    => '?integer',
        'enabled'      => 'boolean',
    ];

    protected $dates = ['last_synced_at', 'created_at', 'updated_at'];

    public function httpsUrl(): string
    {
        return (string) preg_replace('/^webcal:/i', 'https:', $this->ics_url);
    }
}
