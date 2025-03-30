<?php

namespace App\Filament\Admin\Resources\WebhookResource\Pages;

use App\Filament\Admin\Resources\WebhookResource;
use App\Models\WebhookConfiguration;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class DiscordPreview extends Page
{
    protected static string $resource = WebhookResource::class;

    protected static string $view = 'filament.admin.pages.discord-preview';

    public Model|int|string|null $record;

    /** @var array<mixed> */
    protected array $payload;

    /** @var array<mixed> */
    protected array $messages;

    /** @var array<mixed> */
    protected array $embeds;

    public function mount(): void
    {
        $record = WebhookConfiguration::find($this->record);

        $this->payload = json_decode($record->payload, true);
        $this->messages = array_get($this->payload, 'messages', []);
        $this->embeds = array_get($this->payload, 'embeds', []);
        foreach ($this->embeds as $embed) {
            $embed['color'] ??= '#5865F2';
        }
    }
    
    public static function route(string $record): PageRegistration
    {
        return new PageRegistration(
            page: static::class,
            route: fn (Panel $panel): Route => RouteFacade::get($record, static::class)
                ->middleware(static::getRouteMiddleware($panel))
                ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
                ->where('record', '.*'),
        );
    }
}
