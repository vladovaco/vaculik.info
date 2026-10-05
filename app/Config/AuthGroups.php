<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * Group assigned to a user when none is given.
     */
    public string $defaultGroup = 'guest';

    /**
     * Family roles. See docs/ARCHITEKTURA.md, section 5.
     *
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Správca',
            'description' => 'Rodič-správca. Všetko vrátane správy používateľov a modulov.',
        ],
        'adult' => [
            'title'       => 'Dospelý',
            'description' => 'Druhý rodič. Všetko okrem správy používateľov.',
        ],
        'child' => [
            'title'       => 'Dieťa',
            'description' => 'Vlastný rozvrh, rutiny, úlohy, body a kalendár. Nič finančné.',
        ],
        'guest' => [
            'title'       => 'Hosť',
            'description' => 'Starí rodičia, opatrovateľka. Len čítanie vybraných osôb.',
        ],
    ];

    /**
     * Permissions are module-scoped: "<module>.<action>".
     */
    public array $permissions = [
        'users.manage'      => 'Správa používateľov a rolí',
        'settings.manage'   => 'Nastavenia domácnosti a modulov',
        'household.manage'  => 'Správa osôb v domácnosti',
        'household.view'    => 'Zobrazenie osôb v domácnosti',
        'calendar.manage'   => 'Vytváranie a úprava udalostí',
        'calendar.view'     => 'Zobrazenie kalendára',
        'finance.manage'    => 'Platby, bločky, rozpočet',
        'documents.manage'  => 'Nahrávanie a úprava dokumentov',
        'documents.view'    => 'Zobrazenie dokumentov',
        'contacts.manage'   => 'Správa kontaktov',
        'contacts.view'     => 'Zobrazenie kontaktov',
        'school.manage'     => 'Rozvrhy, Edupage, zmluvy',
        'school.view'       => 'Zobrazenie rozvrhov',
        'health.manage'     => 'Kartičky, lekári, lieky',
        'health.view'       => 'Zobrazenie kartičiek a liekov',
        'tasks.manage'      => 'Zadávanie a schvaľovanie úloh',
        'tasks.own'         => 'Vlastné úlohy, rutiny a odmeny',
        'assistant.use'     => 'Chat s asistentom',
        'deadlines.view'    => 'Zobrazenie termínov',
    ];

    public array $matrix = [
        'admin' => [
            'users.*', 'settings.*', 'household.*', 'calendar.*', 'finance.*',
            'documents.*', 'contacts.*', 'school.*', 'health.*', 'tasks.*', 'assistant.*', 'deadlines.*',
        ],
        'adult' => [
            'household.*', 'calendar.*', 'finance.*', 'documents.*', 'contacts.*',
            'school.*', 'health.*', 'tasks.*', 'assistant.*', 'deadlines.*',
        ],
        'child' => [
            'calendar.view', 'school.view', 'tasks.own', 'deadlines.view',
        ],
        'guest' => [
            'household.view', 'calendar.view', 'contacts.view', 'health.view', 'deadlines.view',
        ],
    ];
}
