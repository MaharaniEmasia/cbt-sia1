<?php

namespace App\Filament\Resources\Migrations;

use App\Filament\Resources\Migrations\Pages\CreateMigration;
use App\Filament\Resources\Migrations\Pages\EditMigration;
use App\Filament\Resources\Migrations\Pages\ListMigrations;
use App\Filament\Resources\Migrations\Schemas\MigrationForm;
use App\Filament\Resources\Migrations\Tables\MigrationsTable;
use App\Models\Migration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MigrationResource extends Resource
{
    protected static ?string $model = Migration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'migration';

    public static function form(Schema $schema): Schema
    {
        return MigrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MigrationsTable::configure($table);
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
            'index' => ListMigrations::route('/'),
            'create' => CreateMigration::route('/create'),
            'edit' => EditMigration::route('/{record}/edit'),
        ];
    }
}
