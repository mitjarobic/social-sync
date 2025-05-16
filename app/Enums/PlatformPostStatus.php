<?php

namespace App\Enums;

enum PlatformPostStatus: string
{
    case DRAFT = 'draft';
    case QUEUED = 'queued';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::QUEUED => 'Queued',
            self::PUBLISHING => 'Publishing',
            self::PUBLISHED => 'Published',
            self::FAILED => 'Failed'
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::QUEUED => 'info',
            self::PUBLISHING => 'warning',
            self::PUBLISHED => 'success',
            self::FAILED => 'danger'
        };
    }
}