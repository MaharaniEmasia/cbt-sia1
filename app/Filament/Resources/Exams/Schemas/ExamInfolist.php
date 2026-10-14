<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Jenis Ujian'),
                TextEntry::make('duration')
                    ->label('Durasi')
                    ->numeric(),
                TextEntry::make('threshold')
                    ->label('Min. Nilai')
                    ->numeric(),
                IconEntry::make('exact_time')
                    ->boolean(),
                TextEntry::make('started_at')
                    ->label('Mulai')
                    ->dateTime(),
                TextEntry::make('expired_at')
                    ->label('Berakhir')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Update')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
