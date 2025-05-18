<?php

namespace App\Models;

class Platform extends BaseModel
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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

    /**
     * Scope a query to only include platforms for the current company.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCurrentCompany($query)
    {
        // Get the current company ID from the request
        $user = request()->user();

        if ($user && $user->currentCompany) {
            return $query->where('company_id', $user->currentCompany->id);
        }

        return $query;
    }
}
