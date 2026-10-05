<?php

declare(strict_types=1);

namespace Modules\Calendar\Models;

use CodeIgniter\Model;
use Modules\Calendar\Entities\CalendarSource;

class CalendarSourceModel extends Model
{
    protected $table         = 'calendar_sources';
    protected $primaryKey    = 'id';
    protected $returnType    = CalendarSource::class;
    protected $useTimestamps = true;
    protected $allowedFields = ['household_id', 'person_id', 'name', 'ics_url', 'color', 'enabled', 'last_synced_at', 'last_error'];

    protected $validationRules = [
        'household_id' => 'required|is_natural_no_zero',
        'name'         => 'required|max_length[80]',
        'ics_url'      => 'required|max_length[1000]|regex_match[/^(https?|webcal):\/\//i]',
        'color'        => 'required|regex_match[/^#[0-9a-fA-F]{6}$/]',
        'person_id'    => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'name'    => ['required' => 'Názov je povinný.'],
        'ics_url' => ['required' => 'Adresa ICS je povinná.', 'regex_match' => 'Adresa musí začínať https:// alebo webcal://.'],
    ];

    /**
     * @return list<CalendarSource>
     */
    public function forHousehold(int $householdId): array
    {
        return $this->where('household_id', $householdId)->orderBy('name')->findAll();
    }

    /**
     * @return list<CalendarSource>
     */
    public function dueForSync(int $maxAgeMinutes): array
    {
        return $this->where('enabled', 1)
            ->groupStart()
            ->where('last_synced_at', null)
            ->orWhere('last_synced_at <', date('Y-m-d H:i:s', time() - $maxAgeMinutes * 60))
            ->groupEnd()
            ->findAll();
    }
}
