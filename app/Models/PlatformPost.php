<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PlatformPost extends Pivot
{
    protected $table = 'platform_post';

    protected $fillable = [
        'post_id',
        'platform_id',
        'scheduled_at',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

}

