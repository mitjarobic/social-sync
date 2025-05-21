<?php

namespace App\Filament\Company\Resources\ImageTemplateResource\Pages;

use App\Filament\Company\Resources\ImageTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImageTemplates extends ListRecords
{
    protected static string $resource = ImageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
