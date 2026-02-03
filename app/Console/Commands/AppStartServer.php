<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppStartServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-server {--dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the application server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = (string) config('app.server_host', '0.0.0.0');
        $port = (int) config('app.server_port', 8000);

        $this->info('Running migrations...');

        $migrateStatus = (int) $this->call('migrate', ['--force' => true, '--seed' => true]);

        if ($migrateStatus !== self::SUCCESS) {
            $this->error('Migration failed. Aborting server start.');

            return $migrateStatus;
        }

        if ($this->option('dev')) {
            $this->info("Starting development server at {$host}:{$port}...");

            return (int) $this->call('serve', [
                '--host' => $host,
                '--port' => $port,
            ]);
        }

        $this->warn('Octane startup is not implemented yet. Use --dev for the built-in server.');

        return self::FAILURE;
    }
}
