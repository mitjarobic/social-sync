<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PlatformPost;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Schemas\PlatformPostSchema;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PlatformPostResource\Pages;
use App\Filament\Company\Resources\PlatformPostResource\RelationManagers;

class PlatformPostResource extends Resource
{
    protected static ?string $model = PlatformPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema(PlatformPostSchema::form());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(PlatformPostSchema::table())
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformPosts::route('/'),
            'create' => Pages\CreatePlatformPost::route('/create'),
            'edit' => Pages\EditPlatformPost::route('/{record}/edit'),
        ];
    }
}
