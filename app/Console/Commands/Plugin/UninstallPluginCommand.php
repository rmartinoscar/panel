<?php

namespace App\Console\Commands\Plugin;

use App\Facades\Plugins;
use App\Models\Plugin;
use Illuminate\Console\Command;

class UninstallPluginCommand extends Command
{
    protected $signature = 'p:plugin:uninstall {id?}';

    protected $description = 'Uninstalls a plugin';

    public function handle(): void
    {
        $id = $this->argument('id') ?? $this->choice('Plugin', Plugin::pluck('name', 'id')->toArray());

        $plugin = Plugin::find($id);

        if (!$plugin) {
            $this->error('Plugin does not exist!');

            return;
        }

        if ($plugin->isUninstalled()) {
            $this->error('Plugin is already uninstalled!');

            return;
        }

        Plugins::uninstallPlugin($plugin);

        $this->info('Plugin uninstalled.');
    }
}
