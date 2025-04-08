<?php

namespace App\Filament\Company\Resources\PostResource\Pages;

use App\Filament\Company\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
