<?php

declare(strict_types=1);

namespace Modules\Household\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int    $id
 * @property string $name
 * @property string $timezone
 */
class Household extends Entity
{
    protected $casts = [
        'id' => 'integer',
    ];
}
