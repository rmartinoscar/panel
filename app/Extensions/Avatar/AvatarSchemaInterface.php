<?php

namespace App\Extensions\Avatar;

use Illuminate\Database\Eloquent\Model;

interface AvatarSchemaInterface
{
    public function getId(): string;

    public function getName(): string;

    public function get(Model $model): ?string;
}
