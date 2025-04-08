<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'platform_id', 'content', 'status'];

    // Relationship to the Company model
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // public function platforms()
    // {
    //     return $this->belongsToMany(Platform::class)
    //         ->withPivot(['scheduled_at']) // include all pivot columns here
    //         ->withTimestamps();
    // }

    public function platformPosts()
    {
        return $this->hasMany(PlatformPost::class);
    }


}
