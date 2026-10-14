<?php

namespace App\Filament\Resources\Migrations\Pages;

use App\Filament\Resources\Migrations\MigrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ManageMigrations extends ManageRecords
{
    protected static string $resource = MigrationResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString('
            <div class="mt-2 rounded-lg border border-amber-400 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950/50">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                    <strong>Peringatan:</strong> Perubahan pada halaman migrasi ini dapat mempengaruhi struktur database secara langsung. Pastikan Anda telah melakukan backup sebelum melanjutkan.
                </p>
            </div>
        ');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
