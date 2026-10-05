<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use CodeIgniter\Shield\Entities\User;

/**
 * A module exposes read-only tools to the assistant. Every tool must enforce
 * the permissions of the asking user: the assistant never bypasses authorization.
 */
interface AssistantToolProvider
{
    /**
     * @return list<array{name: string, description: string, input_schema: array<string, mixed>, handler: callable}>
     */
    public function assistantTools(User $user): array;
}
