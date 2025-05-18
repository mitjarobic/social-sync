<?php

namespace App\Models;

use App\Enums\PlatformPostStatus;
use App\Models\User;

class PlatformPost extends BaseModel
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
        'shares',  // Note: For Instagram regular posts, shares will always be 0 as Instagram API doesn't provide share metrics for regular posts (only for stories)
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

    /**
     * Set the scheduled_at attribute to UTC
     *
     * @param  string|null  $value
     * @return void
     */
    public function setScheduledAtAttribute($value)
    {
        $this->attributes['scheduled_at'] = $value ? \App\Support\TimezoneHelper::toUTC($value) : null;
    }

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

    public function user()
    {
        return $this->belongsTo(User::class, 'company_id', 'current_company_id');
    }

    protected static function booted()
    {
        // When a platform post is created, update the parent post status
        static::created(function ($platformPost) {
            if ($platformPost->post) {
                $platformPost->post->updateStatus();
            }
        });

        // When a platform post is updated, update the parent post status
        static::updated(function ($platformPost) {
            if ($platformPost->isDirty('status') && $platformPost->post) {
                $platformPost->post->updateStatus();
            }
        });

        // When a platform post is deleted, update the parent post status
        static::deleted(function ($platformPost) {
            if ($platformPost->post) {
                $platformPost->post->updateStatus();
            }
        });
    }
}