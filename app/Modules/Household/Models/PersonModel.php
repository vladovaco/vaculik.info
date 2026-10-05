<?php

declare(strict_types=1);

namespace Modules\Household\Models;

use CodeIgniter\Model;
use Modules\Household\Entities\Person;

class PersonModel extends Model
{
    protected $table          = 'persons';
    protected $primaryKey     = 'id';
    protected $returnType     = Person::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'household_id', 'first_name', 'last_name', 'nickname', 'role', 'birth_date', 'color', 'sort_order',
    ];

    protected $validationRules = [
        'household_id' => 'required|is_natural_no_zero',
        'first_name'   => 'required|max_length[80]',
        'last_name'    => 'permit_empty|max_length[80]',
        'nickname'     => 'permit_empty|max_length[40]',
        'role'         => 'required|in_list[adult,child,guest]',
        'birth_date'   => 'permit_empty|valid_date[Y-m-d]',
        'color'        => 'required|regex_match[/^#[0-9a-fA-F]{6}$/]',
        'sort_order'   => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'first_name' => ['required' => 'Meno je povinné.'],
        'role'       => ['in_list' => 'Neplatná rola.'],
        'color'      => ['regex_match' => 'Farba musí byť v tvare #rrggbb.'],
    ];

    /**
     * @return list<Person>
     */
    public function forHousehold(int $householdId): array
    {
        return $this->where('household_id', $householdId)
            ->orderBy('sort_order')
            ->orderBy('first_name')
            ->findAll();
    }
}
