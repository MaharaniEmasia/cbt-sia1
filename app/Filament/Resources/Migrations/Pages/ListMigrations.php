<?php

namespace App\Filament\Resources\Migrations\Pages;

use App\Filament\Resources\Migrations\MigrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMigrations extends ListRecords
{
    protected static string $resource = MigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
