<?php

namespace App\Extensions\Avatar\Schemas;

use App\Extensions\Avatar\AvatarSchemaInterface;
use Illuminate\Database\Eloquent\Model;

class GravatarSchema implements AvatarSchemaInterface
{
    public function getId(): string
    {
        return 'gravatar';
    }

    public function getName(): string
    {
        return 'Gravatar';
    }

    public function get(Model $model): ?string
    {
        if (!property_exists($model, 'email')) {
            return null;
        }

        return 'https://gravatar.com/avatar/' . md5($model->email);
    }
}
