<?php

namespace App\Filament\Components\Forms\Actions;

use App\Models\Server;
use App\Services\Servers\HealthcheckCommandService;
use App\Services\Servers\StartupCommandService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PreviewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'preview';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (Get $get, Set $set, Server $server) {
            $active = $get('previewing');
            $set('previewing', !$active);

            if ($get('startup')) {
                $set('startup', $active ? $server->startup : fn (Server $server, StartupCommandService $service) => $service->handle($server));
            }

            if ($get('healthcheck')) {
                $set('healthcheck', $active ? $server->healthcheck : fn (Server $server, HealthcheckCommandService $service) => $service->handle($server));
            }
        });
    }
}
