<?php

namespace App\Filament\Company\Resources\PlatformPostRelationManagerResource\RelationManagers;


use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Schemas\PlatformPostSchema;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
