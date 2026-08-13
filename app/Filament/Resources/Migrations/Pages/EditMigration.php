<?php

namespace App\Filament\Resources\Migrations\Pages;

use App\Filament\Resources\Migrations\MigrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMigration extends EditRecord
{
    protected static string $resource = MigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
