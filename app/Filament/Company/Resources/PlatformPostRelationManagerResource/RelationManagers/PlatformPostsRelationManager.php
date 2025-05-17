<?php

namespace App\Filament\Company\Resources\PlatformPostRelationManagerResource\RelationManagers;


use App\Filament\Company\Resources\Schemas\PlatformPostSchema;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Company\Resources\PlatformPostResource\Actions\DeletePlatformPostAction;
use Filament\Resources\RelationManagers\RelationManager;

class PlatformPostsRelationManager extends RelationManager
{
    protected static string $relationship = 'platformPosts';

    public function form(Form $form): Form
    {
        return $form->schema(PlatformPostSchema::form());

    }

    public function table(Table $table): Table
    {
        return $table->columns(PlatformPostSchema::table())
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeletePlatformPostAction::make('delete')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
