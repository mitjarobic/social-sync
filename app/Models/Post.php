<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Support\ImageStore;
use App\Services\SocialMediaImageGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'platform_id',
        'content',
        'image_content',
        'image_author',
        'image_path',
        'content_font',
        'content_font_size',
        'content_font_color',
        'author_font',
        'author_font_size',
        'author_font_color',
        'image_bg_color',
        'image_bg_image_path',
        'image_options',
        'status',
        'image_template_id',
        'use_custom_image_settings'
    ];

    protected $casts = [
        'status' => PostStatus::class,
        'image_options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'use_custom_image_settings' => 'boolean',
        'content_font_size' => 'integer',
        'author_font_size' => 'integer'
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

    /**
     * Get the image template associated with this post
     */
    public function imageTemplate()
    {
        return $this->belongsTo(ImageTemplate::class);
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
        //     } // // Handle both created and updated events
        // static::created(function ($post) {
        //     // When a post is created with SCHEDULED status, queue all platform posts
        //     if ($post->status === \App\Enums\PostStatus::SCHEDULED) {
        //         $post->platformPosts()
        //             ->where('status', \App\Enums\PlatformPostStatus::DRAFT)
        //             ->update(['status' => \App\Enums\PlatformPostStatus::QUEUED]);
        //     }

        //     $post->createOrUpdateImageIfNecessary();
        // });

        //     $post->createOrUpdateImageIfNecessary();
        // });

        static::saved(function ($post) {
            // When post is published, queue all platform posts
            if ($post->status === \App\Enums\PostStatus::PUBLISHING) {
                $post->platformPosts()
                    ->where('status', \App\Enums\PlatformPostStatus::DRAFT)
                    ->update(['status' => \App\Enums\PlatformPostStatus::QUEUED]);

                \App\Jobs\PublishPlatformPosts::dispatch($post);
            }

            if ($post->status === \App\Enums\PostStatus::SCHEDULED) {
                $post->platformPosts()
                    ->where('status', \App\Enums\PlatformPostStatus::DRAFT)
                    ->update(['status' => \App\Enums\PlatformPostStatus::QUEUED]);
            }

            $post->createOrUpdateImageIfNecessary();
        });
    }

    /**
     * Update the post status based on the status of its platform posts
     *
     * Status logic:
     * - If any platform post is failed -> FAILED
     * - If all platform posts are published -> PUBLISHED
     * - If any platform post is publishing -> PUBLISHING
     * - If any platform post is queued -> SCHEDULED
     * - Otherwise -> DRAFT
     */
    public function updateStatus()
    {
        // Refresh the relationship to ensure we have the latest data
        $this->load('platformPosts');

        // If there are no platform posts, don't change the status
        if ($this->platformPosts->isEmpty()) {
            return;
        }

        // Determine the new status based on platform posts
        $newStatus = null;

        if ($this->platformPosts->contains('status', \App\Enums\PlatformPostStatus::FAILED)) {
            $newStatus = \App\Enums\PostStatus::FAILED;
        } elseif (
            $this->platformPosts->contains('status', \App\Enums\PlatformPostStatus::PUBLISHED) &&
            !$this->platformPosts->contains(fn($pp) => $pp->status !== \App\Enums\PlatformPostStatus::PUBLISHED)
        ) {
            $newStatus = \App\Enums\PostStatus::PUBLISHED;
        } elseif ($this->platformPosts->contains('status', \App\Enums\PlatformPostStatus::PUBLISHING)) {
            $newStatus = \App\Enums\PostStatus::PUBLISHING;
        } elseif ($this->platformPosts->contains('status', \App\Enums\PlatformPostStatus::QUEUED)) {
            $newStatus = \App\Enums\PostStatus::SCHEDULED;
        } else {
            $newStatus = \App\Enums\PostStatus::DRAFT;
        }

        // Only update if the status has changed
        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->save();

            \Illuminate\Support\Facades\Log::info('Updated post status', [
                'post_id' => $this->id,
                'new_status' => $newStatus->value,
                'platform_posts' => $this->platformPosts->pluck('status', 'id')
            ]);
        }
    }

    public function createOrUpdateImageIfNecessary()
    {
        if (
            $this->image_content &&
            $this->isDirty([
                'image_content',
                'image_author',
                'content_font',
                'content_font_size',
                'content_font_color',
                'author_font',
                'author_font_size',
                'author_font_color',
                'image_bg_color',
                'image_bg_image_path',
                'image_options',
                'image_template_id',
                'use_custom_image_settings',
            ])
        ) {

            $this->image_path = $this->image_path ?? 'posts/' . now()->timestamp . '.jpg';

            // Get the template if one is selected
            $template = null;
            if ($this->image_template_id) {
                $template = $this->imageTemplate;
            }

            // Prepare options for image generation
            $options = [];

            if ($template && !$this->use_custom_image_settings) {
                // Use template settings
                $options = [
                    // Content-specific font settings
                    'contentFont' => $template->content_font_family ?? $template->font_family ?? 'sansSerif.ttf',
                    'contentFontSize' => $template->content_font_size ?? $template->font_size ?? 112,
                    'contentFontColor' => $template->content_font_color ?? $template->font_color ?? '#FFFFFF',

                    // Author-specific font settings
                    'authorFont' => $template->author_font_family ?? $template->font_family ?? 'sansSerif.ttf',
                    'authorFontSize' => $template->author_font_size ?? ($template->font_size ? intval($template->font_size * 0.7) : 78),
                    'authorFontColor' => $template->author_font_color ?? $template->font_color ?? '#FFFFFF',

                    // Background settings
                    'bgColor' => $template->background_type === 'color' ? $template->background_color : '#000000',
                    'bgImagePath' => $template->background_type === 'image' ? $template->background_image : null,

                    // Layout settings
                    'extraOptions' => [
                        'textAlignment' => $template->text_alignment ?? 'center',
                        'textPosition' => $template->text_position ?? 'middle',
                        'padding' => $template->padding ?? 20,
                    ],
                ];
            } else {
                // Use custom settings or fallback to defaults
                $options = [
                    // Content-specific font settings
                    'contentFont' => $this->image_content_font ?? 'sansSerif.ttf',
                    'contentFontSize' => $this->image_content_font_size ?? 112,
                    'contentFontColor' => $this->image_content_font_color ?? '#FFFFFF',

                    // Author-specific font settings
                    'authorFont' => $this->author_font ?? 'sansSerif.ttf',
                    'authorFontSize' => $this->author_font_size ?? 78,
                    'authorFontColor' => $this->author_font_color ?? '#FFFFFF',

                    // Background settings
                    'bgColor' => $this->image_bg_color ?? '#000000',
                    'bgImagePath' => $this->image_bg_image_path ?? null,

                    // Layout settings
                    'extraOptions' => $this->image_options ?? [],
                ];
            }

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
