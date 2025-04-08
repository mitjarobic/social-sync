<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = ['name'];

    // Relationship to the Company model
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}
