<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Override;

#[Fillable([
    'name',
    'email',
    'password',
    'username',
    'phone',
    'is_staff',
    'photo_path'
])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Autorisasi user agar dapat login ke filament panel
    #[Override]
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->is_staff;
        }

        if ($panel->getId() === 'test') {
            return ! $this->is_staff;
        }

        return false;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
        ];
    }

    // Mengambil URL avatar Filament
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        // Jika photo_path berupa URL eksternal (misal UI Avatars)
        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        // Bersihkan prefix jika tersimpan berlebihan
        $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $this->photo_path), '/');

        // Cek apakah berkas fisik ada di disk public
        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::url($cleanPath);
        }

        return null;
    }

    protected static function boot()
    {
        parent::boot();

        // Hapus foto lama dari storage saat photo_path diupdate
        static::updating(function (User $user) {
            if ($user->isDirty('photo_path') && $user->getOriginal('photo_path')) {
                $oldPath = ltrim(str_replace(['public/', 'storage/'], '', $user->getOriginal('photo_path')), '/');
                Storage::disk('public')->delete($oldPath);
            }
        });

        // Hapus foto dari storage saat user dihapus
        static::deleting(function (User $user) {
            if ($user->photo_path) {
                $path = ltrim(str_replace(['public/', 'storage/'], '', $user->photo_path), '/');
                Storage::disk('public')->delete($path);
            }
        });
    }

    // relasi ke tabel student (HasOne sebaiknya berbentuk singular)
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }
}
