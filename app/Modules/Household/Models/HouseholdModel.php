<?php

declare(strict_types=1);

namespace Modules\Household\Models;

use CodeIgniter\Model;
use Modules\Household\Entities\Household;

class HouseholdModel extends Model
{
    protected $table         = 'households';
    protected $primaryKey    = 'id';
    protected $returnType    = Household::class;
    protected $allowedFields = ['name', 'timezone'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'name'     => 'required|max_length[120]',
        'timezone' => 'permit_empty|timezone',
    ];
}
