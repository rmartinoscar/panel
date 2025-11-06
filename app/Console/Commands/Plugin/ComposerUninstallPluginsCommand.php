<?php

namespace App\Console\Commands\Plugin;

use App\Facades\Plugins;
use App\Models\Plugin;
use Exception;
use Illuminate\Console\Command;

class ComposerUninstallPluginsCommand extends Command
{
    protected $signature = 'p:plugin:composer-uninstall {id?}';

    protected $description = 'Runs "composer remove" on all installed plugins.';

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
            $this->warn('No plugins to be uninstalled');

            return;
        }

        foreach ($plugins as $plugin) {
            if (!$plugin->isDisabled()) {
                continue;
            }

            try {
                Plugins::removeComposerPackages($plugin);
            } catch (Exception $exception) {
                report($exception);

                $this->error($exception->getMessage());
            }
        }
    }
}
