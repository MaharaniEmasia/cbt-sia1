<?php

namespace App\Filament\Test\Resources\Exams;

use App\Filament\Test\Resources\ExamAttempts\ExamAttemptResource;
use App\Filament\Test\Resources\Exams\Pages\ManageExams;
use App\Filament\Test\Resources\Exams\Pages\StartingExam;
use App\Models\Exam;
use App\Models\ExamAttempt;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Sesi Ujian';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Ujian';

    // Cache riwayat ujian selesai berdasarkan user_id dan submitted_at
    protected static function finishedHistories(): Collection
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $userId = Auth::id(); // Menggunakan Facade Auth agar lebih aman dan terbebas dari undefined method

        if (! $userId) {
            return $cache = collect();
        }

        return $cache = ExamAttempt::query()
            ->where('user_id', $userId) // Disesuaikan dengan kolom user_id di migrasi
            ->whereNotNull('submitted_at') // Disesuaikan dengan kolom submitted_at di migrasi
            ->pluck('id', 'exam_id');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (EloquentBuilder $query) {
                $now = now();

                $query
                    ->where('is_available', true)
                    ->where(function (EloquentBuilder $q) use ($now) {
                        // Mode waktu pasti
                        $q->where(function (EloquentBuilder $q) use ($now) {
                            $q->where('exact_time', true)
                                ->whereRaw(
                                    'DATE_ADD(started_at, INTERVAL duration MINUTE) >= ?',
                                    [$now]
                                );
                        })
                            // Mode rentang waktu
                            ->orWhere(function (EloquentBuilder $q) use ($now) {
                                $q->where('exact_time', false)
                                    ->where('expired_at', '>=', $now);
                            });
                    });
            })
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Ujian')
                    ->searchable(),
                TextColumn::make('duration')
                    ->label('Durasi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('Min. Score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d F Y, H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('start')
                    ->label('Mulai ujian')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('primary')
                    ->button()
                    ->visible(fn($record) => ! static::finishedHistories()->has($record->id))
                    ->disabled(
                        fn($record) => $record->started_at >= now()
                    )
                    ->url(
                        fn($record) => route(
                            StartingExam::getRouteName(),
                            ['exam' => $record]
                        )
                    ),

                // Sudah dikerjakan: tanda selesai (tidak bisa diklik)
                Action::make('done')
                    ->label('Sudah dikerjakan')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->button()
                    ->disabled()
                    ->visible(fn($record) => static::finishedHistories()->has($record->id)),

                // Sudah dikerjakan: lihat hasil di halaman riwayat
                Action::make('result')
                    ->label('Lihat hasil')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->button()
                    ->visible(fn($record) => static::finishedHistories()->has($record->id))
                    ->url(fn($record) => ExamAttemptResource::getUrl('view', [
                        'record' => static::finishedHistories()->get($record->id),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExams::route('/'),
            'mulai' => StartingExam::route('/{exam}/mulai'),
        ];
    }
}
