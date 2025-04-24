<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'platform_id', 'content', 'image_content', 'image_author', 'status'];

    protected $casts = [
        'status' => PostStatus::class
    ];

    // Relationship to the Company model
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function platformPosts()
    {
        return $this->hasMany(PlatformPost::class)->with('platform');
    }

    protected static function booted()
    {
        static::updated(function ($post) {
            // When post is published, queue all platform posts
            if ($post->status === \App\Enums\PostStatus::PUBLISHING) {
                $post->platformPosts()
                    ->where('status', \App\Enums\PlatformPostStatus::DRAFT)
                    ->update(['status' => \App\Enums\PlatformPostStatus::QUEUED]);

                \App\Jobs\DispatchPlatformPosts::dispatch($post);
            }

            if ($post->status === \App\Enums\PostStatus::SCHEDULED) {
                $post->platformPosts()
                    ->where('status', \App\Enums\PlatformPostStatus::DRAFT)
                    ->update(['status' => \App\Enums\PlatformPostStatus::QUEUED]);
            }
        });
    }

    public function updateStatus()
    {
        if ($this->platformPosts()->where('status', 'failed')->exists()) {
            $this->status = \App\Enums\PostStatus::FAILED;
        } elseif ($this->platformPosts()->where('status', '!=', 'published')->doesntExist()) {
            $this->status = \App\Enums\PostStatus::PUBLISHED;
        }

        // $this->save();
    }
}
