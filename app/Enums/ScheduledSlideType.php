<?php

namespace App\Enums;

enum ScheduledSlideType: string
{
    case WEATHER = 'WEATHER';
    case ORDERSLIST = 'ORDERSLIST';
    case EVENTS = 'EVENTS';
    case MENUS = 'MENUS';
    case PICS = 'PICS';
    case VIDEOS = 'VIDEOS';
    case KARAOKE = 'KARAOKE';

    /**
     * Get all slide type values.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Return human-friendly labels for slide types.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::WEATHER->value => __('Weather Forecast'),
            self::ORDERSLIST->value => __('Orders List'),
            self::EVENTS->value => __('Events'),
            self::MENUS->value => __('Menus'),
            self::PICS->value => __('Pictures'),
            self::VIDEOS->value => __('Videos'),
            self::KARAOKE->value => __('Karaoke'),
        ];
    }

    /**
     * Default standard schedule.
     *
     * @return string[]
     */
    public static function defaultSchedule(): array
    {
        return [
            self::WEATHER->value,
            self::ORDERSLIST->value,
            self::EVENTS->value,
            self::ORDERSLIST->value,
            self::MENUS->value,
            self::EVENTS->value,
            self::ORDERSLIST->value,
            self::PICS->value,
            self::VIDEOS->value,
            self::ORDERSLIST->value,
            self::EVENTS->value,
            self::MENUS->value,
            self::PICS->value,
            self::ORDERSLIST->value,
        ];
    }

    /**
     * Parse text input (lines or commas) into a clean list of slide types.
     *
     * @return string[]|null
     */
    public static function parseSchedule(string|array|null $input): ?array
    {
        if (is_null($input)) {
            return null;
        }

        if (is_array($input)) {
            $items = $input;
        } else {
            $items = preg_split('/[\r\n,]+/', (string) $input, -1, PREG_SPLIT_NO_EMPTY);
        }

        $filtered = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $clean = strtoupper(trim($item));
                if ($clean !== '') {
                    $filtered[] = $clean;
                }
            }
        }

        return ! empty($filtered) ? array_values($filtered) : null;
    }

    /**
     * Validate an array of slide types against known cases.
     * Returns an array of invalid slide type strings, if any.
     *
     * @param  string[]  $schedule
     * @return string[]
     */
    public static function validateSchedule(array $schedule): array
    {
        return array_values(array_diff($schedule, self::values()));
    }
}
