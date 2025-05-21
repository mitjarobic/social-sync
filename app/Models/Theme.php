<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'is_default',
        'fonts',
        'font_sizes',
        'colors',
        'paddings',
        'background_images',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'fonts' => 'array',
        'font_sizes' => 'array',
        'colors' => 'array',
        'paddings' => 'array',
        'background_images' => 'array',
    ];

    /**
     * Get the company that owns the theme
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the image templates that use this theme
     */
    public function imageTemplates(): HasMany
    {
        return $this->hasMany(ImageTemplate::class);
    }
}
