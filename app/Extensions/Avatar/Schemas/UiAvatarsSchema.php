<?php

namespace App\Extensions\Avatar\Schemas;

use App\Extensions\Avatar\AvatarSchemaInterface;
use Illuminate\Database\Eloquent\Model;

class UiAvatarsSchema implements AvatarSchemaInterface
{
    public function getId(): string
    {
        return 'uiavatars';
    }

    public function getName(): string
    {
        return 'UI Avatars';
    }

    public function get(Model $model): ?string
    {
        // UI Avatars is the default of filament so just return null here
        return null;
    }
}
