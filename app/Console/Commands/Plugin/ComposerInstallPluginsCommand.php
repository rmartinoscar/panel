<?php

namespace App\Console\Commands\Plugin;

use App\Facades\Plugins;
use App\Models\Plugin;
use Exception;
use Illuminate\Console\Command;

class ComposerInstallPluginsCommand extends Command
{
    protected $signature = 'p:plugin:composer-install {id?}';

    protected $description = 'Runs "composer require" on specific or all installed plugins.';

    public function handle(): void
    {
        if ($id = $this->argument('id')) {
            if ($id === 'all') {
                $plugins = Plugin::all();
            } else {
                $plugin = Plugin::find($id);
                if (!$plugin) {
                    $this->error('Plugin does not exist!');

                    return;
                }
                $plugins[] = $plugin;
            }
        } else {
            $plugins = $this->choice('Plugin', array_merge(['all' => 'All'], Plugin::pluck('name', 'id')->toArray()));
            if ($plugins === ['all']) {
                $plugins = Plugin::all();
            }
        }

        if (count($plugins) === 0) {
            $this->warn('No plugins to be installed');

            return;
        }

        foreach ($plugins as $plugin) {
            if (!$plugin->shouldLoad()) {
                continue;
            }

            try {
                Plugins::requireComposerPackages($plugin);
            } catch (Exception $exception) {
                report($exception);

                $this->error($exception->getMessage());
            }
        }
    }
}
