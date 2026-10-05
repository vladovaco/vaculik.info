<?php

declare(strict_types=1);

namespace Modules\Contacts\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int         $id
 * @property int         $household_id
 * @property int|null    $person_id
 * @property string      $name
 * @property string|null $organization
 * @property string      $kind
 * @property string|null $phone
 * @property string|null $phone2
 * @property string|null $email
 * @property string|null $address
 * @property string|null $web
 * @property string|null $note
 */
class Contact extends Entity
{
    public const KINDS = [
        'lekar'  => 'Lekár',
        'skola'  => 'Škola',
        'skolka' => 'Škôlka',
        'kruzok' => 'Krúžok',
        'servis' => 'Servis',
        'urad'   => 'Úrad',
        'rodina' => 'Rodina',
        'priatelia' => 'Priatelia',
        'iny'    => 'Iný',
    ];

    protected $casts = [
        'id'           => 'integer',
        'household_id' => 'integer',
        'person_id'    => '?integer',
    ];

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }

    public function telHref(?string $phone = null): string
    {
        return 'tel:' . preg_replace('/[^+0-9]/', '', $phone ?? (string) $this->phone);
    }
}
