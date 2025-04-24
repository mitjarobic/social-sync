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
}