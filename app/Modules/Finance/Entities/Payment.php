<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * @property int         $id
 * @property int         $household_id
 * @property int|null    $person_id
 * @property string      $title
 * @property string|null $payee
 * @property string|null $iban
 * @property string|null $variable_symbol
 * @property string|null $specific_symbol
 * @property string|null $constant_symbol
 * @property string      $amount
 * @property string      $currency
 * @property string      $category
 * @property string      $recurrence
 * @property Time        $due_at
 * @property Time|null   $paid_at
 * @property int|null    $paid_by_person_id
 * @property int|null    $document_id
 * @property string|null $note
 */
class Payment extends Entity
{
    public const CATEGORIES = [
        'skola'   => 'Škola',
        'skolka'  => 'Škôlka',
        'strava'  => 'Strava',
        'kruzok'  => 'Krúžky',
        'zdravie' => 'Zdravie',
        'byvanie' => 'Bývanie',
        'auto'    => 'Auto',
        'poistenie' => 'Poistenie',
        'iny'     => 'Iné',
    ];

    public const RECURRENCES = [
        'none'      => 'Jednorazová',
        'weekly'    => 'Týždenne',
        'monthly'   => 'Mesačne',
        'quarterly' => 'Štvrťročne',
        'yearly'    => 'Ročne',
    ];

    protected $casts = [
        'id'                => 'integer',
        'household_id'      => 'integer',
        'person_id'         => '?integer',
        'paid_by_person_id' => '?integer',
        'document_id'       => '?integer',
    ];

    protected $dates = ['due_at', 'paid_at', 'created_at', 'updated_at', 'deleted_at'];

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isRecurring(): bool
    {
        return $this->recurrence !== 'none';
    }

    public function daysLeft(?Time $today = null): int
    {
        return (int) ($today ?? Time::now())->setTime(0, 0)->difference($this->due_at->setTime(0, 0))->getDays();
    }

    public function isOverdue(?Time $today = null): bool
    {
        return ! $this->isPaid() && $this->daysLeft($today) < 0;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function recurrenceLabel(): string
    {
        return self::RECURRENCES[$this->recurrence] ?? $this->recurrence;
    }

    public function amountFormatted(): string
    {
        return number_format((float) $this->amount, 2, ',', ' ') . ' €';
    }

    public function ibanFormatted(): string
    {
        return trim(chunk_split((string) $this->iban, 4, ' '));
    }

    /**
     * Due date of the next occurrence of a recurring payment.
     */
    public function nextDueAt(): ?Time
    {
        return match ($this->recurrence) {
            'weekly'    => $this->due_at->addDays(7),
            'monthly'   => self::addMonthsClamped($this->due_at, 1),
            'quarterly' => self::addMonthsClamped($this->due_at, 3),
            'yearly'    => self::addMonthsClamped($this->due_at, 12),
            default     => null,
        };
    }

    /**
     * Adds months keeping the day of month, clamped to the target month's length
     * (31 Jan + 1 month = 28/29 Feb, not 3 Mar).
     */
    public static function addMonthsClamped(Time $date, int $months): Time
    {
        $year  = (int) $date->getYear();
        $month = (int) $date->getMonth() + $months;
        $year += intdiv($month - 1, 12);
        $month = (($month - 1) % 12) + 1;
        $last  = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');

        return Time::create($year, $month, min((int) $date->getDay(), $last), 0, 0, 0, $date->getTimezone());
    }

    public function statusBadge(?Time $today = null): string
    {
        if ($this->isPaid()) {
            return 'ok';
        }
        $days = $this->daysLeft($today);

        return $days < 0 ? 'danger' : ($days <= 7 ? 'warning' : 'muted');
    }

    public function statusLabel(?Time $today = null): string
    {
        if ($this->isPaid()) {
            return 'zaplatené ' . $this->paid_at->format('j.n.');
        }
        $days = $this->daysLeft($today);
        if ($days < 0) {
            return 'po splatnosti ' . abs($days) . ' d.';
        }

        return match ($days) {
            0       => 'dnes',
            1       => 'zajtra',
            default => 'o ' . $days . ' dní',
        };
    }
}
