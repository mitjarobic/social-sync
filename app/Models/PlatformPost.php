<?php

namespace App\Models;

use App\Enums\PlatformPostStatus;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlatformPost extends Pivot
{
    protected $table = 'platform_post';

    protected $fillable = [
        'post_id',
        'platform_id',
        'status',
        'external_id',
        'external_url',
        'metadata',
        'scheduled_at',
        'posted_at',

    ];

    protected $casts = [
        'status' => PlatformPostStatus::class
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}