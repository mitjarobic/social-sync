<?php

namespace App\Models;

use App\Enums\PlatformPostStatus;
use Illuminate\Database\Eloquent\Model;


class PlatformPost extends Model
{
    protected $table = 'platform_post';

    protected $fillable = [
        'company_id',
        'platform_id',
        'post_id',
        'status',
        'external_id',
        'external_url',
        'metadata',
        'reach',
        'likes',
        'comments',
        'shares',
        'metrics_updated_at',
        'scheduled_at',
        'posted_at',
    ];

    protected $casts = [
        'status' => PlatformPostStatus::class,
        'metadata' => 'array',
        'metrics_updated_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
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