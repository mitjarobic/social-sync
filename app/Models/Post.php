<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use App\Services\SocialMediaImageGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_id',
        'content',
        'image_content',
        'image_author',
        'image_path',
        'image_font',
        'image_font_size',
        'image_font_color',
        'image_bg_color',
        'image_bg_image_path',
        'image_options',
        'status'
    ];

    protected $casts = [
        'status' => PostStatus::class,
        'image_options' => 'array'
    ];

    // Relationship to the Company model
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function platformPosts()
    {
        return $this->hasMany(PlatformPost::class);
    }

    protected static function booted()
    {
        // // Handle both created and updated events
        // static::created(function ($post) {
        //     // When a post is created with SCHEDULED status, queue all platform posts
        //     if ($post->status === \App\Enums\PostStatus::SCHEDULED) {
        //         $post->platformPosts()
        //             ->where('status', \App\Enums\PlatformPostStatus::DRAFT)
        //             ->update(['status' => \App\Enums\PlatformPostStatus::QUEUED]);
        //     }

        //     $post->createOrUpdateImageIfNecessary();
        // });

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

            $post->createOrUpdateImageIfNecessary();
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

    public function createOrUpdateImageIfNecessary()
    {

        if ($this->isDirty('image_content') ||
            $this->isDirty('image_author') ||
            $this->isDirty('image_font') ||
            $this->isDirty('image_font_size') ||
            $this->isDirty('image_font_color') ||
            $this->isDirty('image_bg_color') ||
            $this->isDirty('image_bg_image_path') ||
            $this->isDirty('image_options')
        ) {

            $this->image_path = $this->image_path ?? 'posts/' . now()->timestamp . '.jpg';

            // Prepare options for image generation
            $options = [
                'font' => $this->image_font ?? 'sansSerif.ttf',
                'fontSize' => $this->image_font_size ?? 112,
                'fontColor' => $this->image_font_color ?? '#FFFFFF',
                'bgColor' => $this->image_bg_color ?? '#000000',
                'bgImagePath' => $this->image_bg_image_path ?? null,
                'extraOptions' => $this->image_options ?? [],
            ];

            $jpegData = SocialMediaImageGenerator::generate(
                $this->image_content,
                $this->image_author,
                $options
            );

            ImageStore::save($this->image_path, $jpegData);
            $this->saveQuietly();
        }
    }

    public function getImageUrlAttribute()
    {
        return \App\Support\ImageStore::url($this->image_path);
    }

}