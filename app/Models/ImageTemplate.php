<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'theme_id',
        'background_type', // 'color' or 'image'
        'background_color',
        'background_image',
        'content_font_family',
        'content_font_size',
        'content_font_color',
        'author_font_family',
        'author_font_size',
        'author_font_color',
        'text_alignment', // 'left', 'center', 'right'
        'text_position', // 'top', 'middle', 'bottom'
        'padding',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'padding' => 'integer',
        'content_font_size' => 'integer',
        'author_font_size' => 'integer',
    ];

    /**
     * Get the company that owns the template
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the theme associated with this template
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the posts that use this template
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
