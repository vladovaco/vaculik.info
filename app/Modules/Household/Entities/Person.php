<?php

declare(strict_types=1);

namespace Modules\Household\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * @property int         $id
 * @property int         $household_id
 * @property string      $first_name
 * @property string|null $last_name
 * @property string|null $nickname
 * @property string      $role
 * @property Time|null   $birth_date
 * @property string      $color
 * @property int         $sort_order
 */
class Person extends Entity
{
    public const ROLE_ADULT = 'adult';
    public const ROLE_CHILD = 'child';
    public const ROLE_GUEST = 'guest';

    public const ROLES = [
        self::ROLE_ADULT => 'Dospelý',
        self::ROLE_CHILD => 'Dieťa',
        self::ROLE_GUEST => 'Hosť',
    ];

    protected $casts = [
        'id'           => 'integer',
        'household_id' => 'integer',
        'sort_order'   => 'integer',
    ];

    protected $dates = ['birth_date', 'created_at', 'updated_at', 'deleted_at'];

    public function displayName(): string
    {
        return $this->nickname !== null && $this->nickname !== '' ? $this->nickname : $this->first_name;
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }

    public function isChild(): bool
    {
        return $this->role === self::ROLE_CHILD;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function age(?Time $today = null): ?int
    {
        if ($this->birth_date === null) {
            return null;
        }

        return $this->birth_date->difference($today ?? Time::now())->getYears();
    }

    /**
     * Two-letter monogram for the avatar circle.
     */
    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->displayName(), 0, 2));
    }
}
