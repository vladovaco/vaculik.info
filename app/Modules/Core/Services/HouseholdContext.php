<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Modules\Household\Entities\Household;
use Modules\Household\Entities\Person;
use Modules\Household\Models\HouseholdModel;
use Modules\Household\Models\PersonModel;

/**
 * Resolves "which household and which person is the current user" for the request.
 * The system is single-household today; the household_id column everywhere keeps
 * the door open for more than one.
 */
final class HouseholdContext
{
    private ?Household $household = null;
    private ?Person $person       = null;
    private bool $resolved        = false;

    public function household(): ?Household
    {
        $this->resolve();

        return $this->household;
    }

    public function householdId(): ?int
    {
        return $this->household()?->id;
    }

    public function person(): ?Person
    {
        $this->resolve();

        return $this->person;
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }
        $this->resolved = true;

        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $personId = $user->person_id ?? null;
        if ($personId !== null) {
            $this->person = model(PersonModel::class)->find((int) $personId);
        }

        $householdId = $this->person?->household_id;
        $this->household = $householdId !== null
            ? model(HouseholdModel::class)->find((int) $householdId)
            : model(HouseholdModel::class)->first();
    }
}
