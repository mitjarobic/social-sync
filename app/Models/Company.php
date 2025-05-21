<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wallo\FilamentCompanies\Company as FilamentCompaniesCompany;
use Wallo\FilamentCompanies\Events\CompanyCreated;
use Wallo\FilamentCompanies\Events\CompanyDeleted;
use Wallo\FilamentCompanies\Events\CompanyUpdated;

class Company extends FilamentCompaniesCompany implements HasAvatar
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_company',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => CompanyCreated::class,
        'updated' => CompanyUpdated::class,
        'deleted' => CompanyDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_company' => 'boolean',
        ];
    }

    public function getFilamentAvatarUrl(): string
    {
        return $this->owner->profile_photo_url;
    }

    public function platforms()
    {
        return $this->hasMany(Platform::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function themes()
    {
        return $this->hasMany(Theme::class);
    }

    public function imageTemplates()
    {
        return $this->hasMany(ImageTemplate::class);
    }

    /**
     * Get the owner of the company.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
