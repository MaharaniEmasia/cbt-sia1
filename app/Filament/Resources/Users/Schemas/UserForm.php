<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        // upload foto
                        FileUpload::make('photo_path')
                            ->label('Upload foto profil')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('user-photos')
                            ->visibility('public') // Tambahkan ini agar file dapat diakses publik
                            ->preserveFilenames(false)
                            ->maxSize(1024)
                            ->imageEditor()
                            ->columnSpanFull()
                            ->alignCenter(),

                        TextInput::make('name')
                            ->label('Nama lengkap')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('email')
                            ->label('Alamat email')
                            ->email(),

                        TextInput::make('username')
                            ->label('Login Username')
                            ->unique(
                                table: 'users',
                                column: 'username',
                                ignoreRecord: true, // Tambahkan ini agar tidak error saat edit user
                            )
                            ->required(),

                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->tel(),

                        TextInput::make('password')
                            ->hiddenOn('edit')
                            ->revealable()
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create'), // Wajib hanya saat buat baru

                        Toggle::make('is_staff')
                            ->required(),
                    ]),
            ]);
    }
}
