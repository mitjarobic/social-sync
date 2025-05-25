<?php

namespace App\Models;

class Platform extends BaseModel
{
    protected $fillable = [
        'label',
        'provider',
        'user_id',
        'company_id',
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

    public function platformPosts()
    {
        return $this->hasMany(PlatformPost::class);
    }

    /**
     * Get the validation rules for the platform
     */
    public static function rules(): array
    {
        return [
            'provider' => 'required|string',
            'company_id' => 'nullable|exists:companies,id',
            'label' => 'required|string|max:255',
            'external_id' => 'required|string',
            'external_name' => 'nullable|string|max:255',
            'external_url' => 'nullable|url',
            'external_token' => 'nullable|string',
            'external_picture_url' => 'nullable|string',
        ];
    }

    /**
     * Get the unique validation rules for provider and company combination
     */
    public static function uniqueRules($platformId = null): array
    {
        $rules = static::rules();

        // Add unique constraint for provider + company_id combination
        $unique = 'unique:platforms,provider,NULL,id,company_id';
        if ($platformId) {
            $unique = "unique:platforms,provider,{$platformId},id,company_id";
        }

        $rules['provider'] .= '|' . $unique;

        return $rules;
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
