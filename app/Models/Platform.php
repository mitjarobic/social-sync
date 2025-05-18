<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = [
        'label',
        'provider',
        'user_id',
        'external_id',
        'external_name',
        'external_url',
        'external_token',
        'external_picture_url'
    ];

    // Relationship to the Company model
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function scopeForCurrentCompany()
    {
        return $this->where('company_id', auth()->user()->currentCompany->id);
    }
}
