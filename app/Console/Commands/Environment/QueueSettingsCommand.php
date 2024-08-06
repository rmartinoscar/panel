<?php

namespace App\Console\Commands\Environment;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use App\Traits\Commands\EnvironmentWriterTrait;
use App\Traits\Commands\RequestRedisSettingsTrait;

class QueueSettingsCommand extends Command
{
    use EnvironmentWriterTrait;
    use RequestRedisSettingsTrait;

    public const QUEUE_DRIVERS = [
        'database' => 'Database (default)',
        'redis' => 'Redis',
        'sync' => 'Synchronous',
    ];

    protected $description = 'Configure queue settings for the Panel.';

    protected $signature = 'p:environment:queue
                            {--driver= : The queue driver backend to use.}
                            {--redis-host= : Redis host to use for connections.}
                            {--redis-pass= : Password used to connect to redis.}
                            {--redis-port= : Port to connect to redis over.}';

    protected array $variables = [];

    /**
     * QueueSettingsCommand constructor.
     */
    public function __construct(private Kernel $console)
    {
        parent::__construct();
    }

    /**
     * Handle command execution.
     */
    public function handle(): int
    {
        $selected = config('queue.default', 'database');
        $this->variables['QUEUE_CONNECTION'] = $this->option('driver') ?? $this->choice(
            'Queue Driver',
            self::QUEUE_DRIVERS,
            array_key_exists($selected, self::QUEUE_DRIVERS) ? $selected : null
        );

        if ($this->variables['QUEUE_CONNECTION'] === 'redis') {
            $this->requestRedisSettings();

            $this->call('p:environment:queue-service', [
                '--use-redis' => true,
                '--overwrite' => true,
            ]);
        }

        $this->writeToEnvironment($this->variables);

        $this->info($this->console->output());

        return 0;
    }
}
