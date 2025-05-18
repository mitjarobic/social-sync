<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;

class TimezoneHelper
{
    /**
     * Get all available timezones with their offsets
     *
     * @return array
     */
    public static function getTimezones(): array
    {
        $timezones = [];
        $identifiers = DateTimeZone::listIdentifiers();

        foreach ($identifiers as $identifier) {
            $now = Carbon::now(new DateTimeZone($identifier));
            $offset = $now->format('P');
            $timezones[$identifier] = "($offset) $identifier";
        }

        // Sort by offset and then by name
        asort($timezones);

        return $timezones;
    }

    /**
     * Get the current user's timezone or default to UTC
     *
     * @return string
     */
    public static function getUserTimezone(): string
    {
        if (Auth::check() && Auth::user()->timezone) {
            return Auth::user()->timezone;
        }

        return 'UTC';
    }

    /**
     * Set the application timezone based on the user's preference
     * Call this method in a service provider or middleware
     *
     * @return void
     */
    public static function setApplicationTimezone(): void
    {
        $timezone = self::getUserTimezone();
        date_default_timezone_set($timezone);
    }

    /**
     * Convert a UTC datetime to the user's timezone
     *
     * @param Carbon|string|null $datetime
     * @return Carbon|null
     */
    public static function toUserTimezone($datetime): ?Carbon
    {
        if (empty($datetime)) {
            return null;
        }

        if (!$datetime instanceof Carbon) {
            $datetime = Carbon::parse($datetime);
        }

        // Create a clone to avoid modifying the original object
        $result = $datetime->copy();

        // Ensure the datetime is in UTC before converting to user timezone
        if ($result->timezone->getName() !== 'UTC') {
            $result->setTimezone('UTC');
        }

        // Convert to user timezone
        return $result->setTimezone(self::getUserTimezone());
    }

    /**
     * Convert a datetime from user's timezone to UTC
     *
     * @param Carbon|string|null $datetime
     * @return Carbon|null
     */
    public static function toUTC($datetime): ?Carbon
    {
        if (empty($datetime)) {
            return null;
        }

        // If it's a string, parse it assuming it's in the user's timezone
        if (!$datetime instanceof Carbon) {
            return Carbon::parse($datetime, self::getUserTimezone())->setTimezone('UTC');
        }

        // If it's already a Carbon instance, create a copy to avoid modifying the original
        $result = $datetime->copy();

        // If it's not already in the user's timezone, convert it first
        $userTimezone = self::getUserTimezone();
        if ($result->timezone->getName() !== $userTimezone) {
            $result->setTimezone($userTimezone);
        }

        // Convert to UTC
        return $result->setTimezone('UTC');
    }

    /**
     * Format a datetime in the user's timezone with localized format
     *
     * @param Carbon|string|null $datetime
     * @param string|null $format Optional custom format, or null to use localized format
     * @return string|null
     */
    public static function formatInUserTimezone($datetime, ?string $format = null): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        $userDateTime = self::toUserTimezone($datetime);

        if ($format) {
            return $userDateTime->format($format);
        }

        // Use localized format based on current locale
        return $userDateTime->isoFormat('LLL'); // Localized long date with time
    }
}
