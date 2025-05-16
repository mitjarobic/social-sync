<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\Schemas\PlatformPostSchema;
use App\Models\PlatformPost;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Company\Resources\PlatformPostResource\Pages;
use App\Filament\Company\Resources\PlatformPostResource\Actions\RefreshMetricsAction;

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
                ActionGroup::make([
                    EditAction::make(),
                    RefreshMetricsAction::make(),
                ])->dropdown(true)
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
