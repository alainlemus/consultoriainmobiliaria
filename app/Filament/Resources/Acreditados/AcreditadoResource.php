<?php

namespace App\Filament\Resources\Acreditados;

use App\Filament\Resources\Acreditados\Pages\CreateAcreditado;
use App\Filament\Resources\Acreditados\Pages\EditAcreditado;
use App\Filament\Resources\Acreditados\Pages\ListAcreditados;
use App\Filament\Resources\Acreditados\Schemas\AcreditadoForm;
use App\Filament\Resources\Acreditados\Tables\AcreditadosTable;
use App\Models\Acreditado;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcreditadoResource extends Resource
{
    protected static ?string $model = Acreditado::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AcreditadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcreditadosTable::configure($table);
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
            'index' => ListAcreditados::route('/'),
            'create' => CreateAcreditado::route('/create'),
            'edit' => EditAcreditado::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
