<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServerResource\Pages;
use App\Models\Mount;
use App\Models\Server;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Get;
use Filament\Resources\Resource;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'tabler-brand-docker';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return trans('admin/server.nav_title');
    }

    public static function getModelLabel(): string
    {
        return trans('admin/server.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('admin/server.model_label_plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('admin/dashboard.server');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function getMountCheckboxList(Get $get): CheckboxList
    {
        $allowedMounts = Mount::all();
        $node = $get('node_id');
        $egg = $get('egg_id');

        if ($node && $egg) {
            $allowedMounts = $allowedMounts->filter(fn (Mount $mount) => ($mount->nodes->isEmpty() || $mount->nodes->contains($node)) &&
                ($mount->eggs->isEmpty() || $mount->eggs->contains($egg))
            );
        }

        return CheckboxList::make('mounts')
            ->label('')
            ->relationship('mounts')
            ->live()
            ->options(fn () => $allowedMounts->mapWithKeys(fn ($mount) => [$mount->id => $mount->name]))
            ->descriptions(fn () => $allowedMounts->mapWithKeys(fn ($mount) => [$mount->id => "$mount->source -> $mount->target"]))
            ->helperText(fn () => $allowedMounts->isEmpty() ? trans('admin/server.no_mounts') : null)
            ->bulkToggleable()
            ->columnSpanFull();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}
