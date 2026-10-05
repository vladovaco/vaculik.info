<?php

declare(strict_types=1);

namespace Modules\Documents\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * @property int         $id
 * @property int         $household_id
 * @property int|null    $person_id
 * @property string      $kind
 * @property string      $title
 * @property string|null $note
 * @property array       $meta
 * @property string|null $ocr_text
 * @property Time|null   $expires_at
 * @property int|null    $created_by
 */
class Document extends Entity
{
    public const KIND_CARD = 'karticka';

    public const KINDS = [
        'zmluva'          => 'Zmluva',
        'blocek'          => 'Bloček / faktúra',
        'karticka'        => 'Kartička poistenca',
        'zarucny_list'    => 'Záručný list',
        'potvrdenie'      => 'Potvrdenie',
        'lekarska_sprava' => 'Lekárska správa',
        'doklad'          => 'Doklad (pas, OP…)',
        'iny'             => 'Iný',
    ];

    public const INSURERS = ['VšZP', 'Dôvera', 'Union'];

    protected $casts = [
        'id'           => 'integer',
        'household_id' => 'integer',
        'person_id'    => '?integer',
        'created_by'   => '?integer',
        'meta'         => 'json-array',
    ];

    protected $dates = ['expires_at', 'created_at', 'updated_at', 'deleted_at'];

    /** @var list<DocumentFile> */
    public array $files = [];

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function isCard(): bool
    {
        return $this->kind === self::KIND_CARD;
    }

    public function daysToExpiry(?Time $today = null): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) ($today ?? Time::now())->setTime(0, 0)->difference($this->expires_at->setTime(0, 0))->getDays();
    }

    public function expiryBadge(): ?string
    {
        $days = $this->daysToExpiry();
        if ($days === null) {
            return null;
        }
        if ($days < 0) {
            return 'danger';
        }

        return $days <= 30 ? 'warning' : 'muted';
    }

    public function metaValue(string $key): string
    {
        return (string) (($this->meta ?? [])[$key] ?? '');
    }

    public function firstImage(): ?DocumentFile
    {
        foreach ($this->files as $file) {
            if ($file->isImage()) {
                return $file;
            }
        }

        return null;
    }
}
