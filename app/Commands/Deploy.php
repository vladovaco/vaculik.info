<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Pull-based deployment for shared hosting without a permanent SSH endpoint.
 *
 * Run from the hosting console or from cron:
 *   php spark app:deploy            # pulls only when origin has new commits
 *   php spark app:deploy --force    # runs every step even without new commits
 *
 * Steps: git fetch -> compare -> git pull --ff-only -> composer install --no-dev
 *        -> migrate --all -> cache:clear. A lock file prevents overlapping runs.
 */
class Deploy extends BaseCommand
{
    protected $group       = 'Family';
    protected $name        = 'app:deploy';
    protected $description = 'Stiahne nové commity z git remote, nainštaluje závislosti a spustí migrácie.';
    protected $usage       = 'app:deploy [--force] [--branch NAME] [--remote NAME]';
    protected $options     = [
        '--force'  => 'Spustí všetky kroky aj bez nových commitov.',
        '--branch' => 'Vetva na nasadenie (predvolene aktuálna vetva).',
        '--remote' => 'Git remote (predvolene origin).',
    ];

    private string $root;

    public function run(array $params): int
    {
        $this->root = rtrim(ROOTPATH, '/');
        $force      = array_key_exists('force', $params) || CLI::getOption('force') !== null;
        $remote     = (string) ($params['remote'] ?? CLI::getOption('remote') ?? 'origin');
        $branch     = (string) ($params['branch'] ?? CLI::getOption('branch') ?? $this->exec('git rev-parse --abbrev-ref HEAD'));

        if ($branch === '' || $branch === 'HEAD') {
            CLI::error('Nepodarilo sa určiť vetvu. Použite --branch.');

            return EXIT_ERROR;
        }

        $lock = fopen(WRITEPATH . 'deploy.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            CLI::write('Deploy už beží, končím.', 'yellow');

            return EXIT_SUCCESS;
        }

        try {
            return $this->deploy($remote, $branch, $force);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function deploy(string $remote, string $branch, bool $force): int
    {
        CLI::write("[" . date('Y-m-d H:i:s') . "] Deploy {$remote}/{$branch}", 'cyan');

        $this->exec("git fetch --quiet {$remote} {$branch}", true);
        $before = $this->exec('git rev-parse HEAD');
        $after  = $this->exec("git rev-parse {$remote}/{$branch}");

        if ($before === $after && ! $force) {
            CLI::write("Aktuálne ({$this->short($before)}), nič nerobím.", 'green');

            return EXIT_SUCCESS;
        }

        if ($before !== $after) {
            CLI::write("Nové commity: {$this->short($before)} -> {$this->short($after)}");
            $this->exec("git pull --ff-only --quiet {$remote} {$branch}", true);
        }

        $this->composerInstall();

        CLI::write('Migrácie...');
        command('migrate --all --no-interaction');

        CLI::write('Čistím cache...');
        command('cache:clear');

        CLI::write('Hotovo. Nasadený commit ' . $this->short($this->exec('git rev-parse HEAD')) . '.', 'green');

        return EXIT_SUCCESS;
    }

    private function composerInstall(): void
    {
        $composer = $this->findComposer();
        CLI::write("Composer ({$composer})...");
        $this->exec(
            "{$composer} install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress 2>&1",
            true,
            ['COMPOSER_ALLOW_SUPERUSER' => '1', 'COMPOSER_NO_INTERACTION' => '1'],
        );
    }

    /**
     * Uses the global composer when present, otherwise a composer.phar in the project root
     * (downloaded once if missing). Shared hostings rarely ship composer on PATH.
     */
    private function findComposer(): string
    {
        $global = trim((string) shell_exec('command -v composer 2>/dev/null'));
        if ($global !== '') {
            return $global;
        }

        $phar = $this->root . '/composer.phar';
        if (! is_file($phar)) {
            CLI::write('Composer nie je nainštalovaný, sťahujem composer.phar...', 'yellow');
            $installer = WRITEPATH . 'composer-setup.php';
            if (copy('https://getcomposer.org/installer', $installer) === false) {
                throw new \RuntimeException('Nepodarilo sa stiahnuť composer installer.');
            }
            $this->exec('php ' . escapeshellarg($installer) . ' --quiet --install-dir=' . escapeshellarg($this->root) . ' --filename=composer.phar', true);
            unlink($installer);
        }

        return 'php ' . escapeshellarg($phar);
    }

    /**
     * Runs a shell command in the project root and returns trimmed stdout.
     *
     * @param array<string, string> $env
     */
    private function exec(string $command, bool $mustSucceed = false, array $env = []): string
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process     = proc_open($command, $descriptors, $pipes, $this->root, $env + getenv());
        if (! is_resource($process)) {
            throw new \RuntimeException("Nepodarilo sa spustiť: {$command}");
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($mustSucceed && $code !== 0) {
            CLI::error(trim($stdout . "\n" . $stderr));

            throw new \RuntimeException("Príkaz zlyhal (kód {$code}): {$command}");
        }

        return trim($stdout);
    }

    private function short(string $sha): string
    {
        return substr($sha, 0, 7);
    }
}
