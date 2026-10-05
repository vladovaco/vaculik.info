<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Minishlink\WebPush\VAPID;

/**
 * Generates the VAPID key pair for Web Push. Run once, paste the output into .env.
 */
class VapidKeys extends BaseCommand
{
    protected $group       = 'Family';
    protected $name        = 'app:vapid';
    protected $description = 'Vygeneruje VAPID kľúče pre Web Push (vložte do .env).';

    public function run(array $params): int
    {
        $keys = VAPID::createVapidKeys();

        CLI::write('Vložte do .env:', 'green');
        CLI::write("push.vapidPublicKey = '{$keys['publicKey']}'");
        CLI::write("push.vapidPrivateKey = '{$keys['privateKey']}'");
        CLI::write("push.subject = 'mailto:rodina@vaculik.info'");

        return EXIT_SUCCESS;
    }
}
