<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Schedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start' => 'date',
        'end' => 'date',
        'disabled' => 'boolean',
    ];

    protected $appends = ['real_start_date', 'real_end_date'];

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('disabled', 0)
            ->where(function (Builder $q) {
                $now = today()->toDateString();
                $q->where(function (Builder $sub) use ($now) {
                    $sub->whereDate('start', '<=', $now)->orWhereNull('start');
                })->where(function (Builder $sub) use ($now) {
                    $sub->whereDate('end', '>=', $now)->orWhereNull('end');
                });
            });
    }

    public function scheduleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Compute the day in format Y-m-d H:i:s of the next occurrence of this event
     *
     * @return mixed|string
     */
    public function getRealStartDateAttribute(): ?string
    {
        return self::calculateStartDateTime(
            $this->start?->toDateString(),
            $this->end?->toDateString(),
            $this->start_time,
            $this->end_time,
            $this->repeat
        );
    }

    /**
     * Get the event end date as Y-m-d H:i:s
     */
    public function getRealEndDateAttribute(): ?string
    {
        return self::calculateEndDateTime(
            $this->start?->toDateString(),
            $this->end?->toDateString(),
            $this->start_time,
            $this->end_time,
            $this->repeat
        );
    }

    /* -------------------------------------------------------------------------- */
    /*                                CALCULATION */
    /* -------------------------------------------------------------------------- */

    public static function calculateDuration(?string $start, ?string $end, ?string $start_time, ?string $end_time, ?string $repeat, ?Carbon $now = null): ?string
    {
        $startDate = self::calculateStartDateTime($start, $end, $start_time, $end_time, $repeat, $now);
        if (! $startDate) {
            return null;
        }

        $endDate = self::calculateEndCarbon($start, $end, $start_time, $end_time, $repeat, $now);
        if (! $endDate) {
            return null;
        }

        return $endDate->diffForHumans($startDate, [
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            'parts' => 6,
            'join' => true,
            'short' => false,
        ]);
    }

    /**
     * Returns a string in the format 'Y-m-d H:i:s' of the next occurrence of this event
     */
    public static function calculateStartDateTime(?string $start, ?string $end, ?string $start_time, ?string $end_time, ?string $repeat, ?Carbon $now = null): ?string
    {
        if (! $start_time) {
            return null;
        }

        if (! $repeat) {
            if (! $start) {
                return null;
            }
            if ($start_time) {
                return Carbon::parse($start.'T'.$start_time)->format('Y-m-d H:i:s');
            }

            return $start;
        }

        if (! $end_time) {
            return null;
        }

        if (! $now) {
            $now = Carbon::now();
        } else {
            $now = $now->clone();
        }

        $dow = self::getDayOfWeek($now);

        // check if it is starting today
        if (strpos($repeat, strval($dow)) !== false) {
            if ((new Carbon($start_time))->greaterThan(new Carbon($end_time))) {
                // the event runs overnight
                $previous_day = self::getYesterdayDayOfWeek($dow);

                // If the event would run yesterday too, and it's not over yet
                if (strpos($repeat, strval($previous_day)) !== false && $now->format('His.u') < (new Carbon($end_time))->format('His.u')) {
                    return $now->subDays(1)->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
                }

                return $now->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
            } else {
                return $now->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
            }
        } elseif (strpos($repeat, strval(self::getYesterdayDayOfWeek($dow))) !== false) {
            // The event started today, it might still be running today
            if ((new Carbon($start_time))->greaterThan(new Carbon($end_time))) {
                // the event runs overnight

                // If the event would run yesterday too, and it's not over yet

                if ($now->format('His.u') < (new Carbon($end_time))->format('His.u')) {
                    return $now->subDays(1)->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
                }
            }
        }

        // Not happening today nor yesterday, look for the next day
        $nextOccurrence = 0;
        // today is 7: 1 2 3 4 5 6
        // today is 6: 7 1 2 3 4 5…
        for ($i = 0; $i < 7 && $nextOccurrence === 0; $i++) {
            $day = (($dow + $i) % 7 + 1);
            if (strpos($repeat, strval($day)) !== false) {
                $nextOccurrence = $day;
                break;
            }
        }
        abort_if($nextOccurrence === 0 || $nextOccurrence > 7, 500);

        // go back to the Carbon way of counting... Sunday is 0
        if ($nextOccurrence === 7) {
            $nextOccurrence = 0;
        }

        return $now->next($nextOccurrence)->setTimeFromTimeString($start_time)->format('Y-m-d H:i:s');
    }

    public static function calculateEndCarbon(?string $start, ?string $end, ?string $start_time, ?string $end_time, ?string $repeat, ?Carbon $now = null): ?Carbon
    {
        $end = self::calculateEndDateTime($start, $end, $start_time, $end_time, $repeat, $now);
        if (! $end) {
            return null;
        }

        return Carbon::parse($end);
    }

    public static function calculateEndDateTime(?string $start, ?string $end, string $start_time, string $end_time, ?string $repeat, ?Carbon $now = null): ?string
    {
        if (! $end_time) {
            return null;
        }

        if (! $repeat) {
            if ($end) {
                if ($end_time) {
                    return Carbon::parse($end.'T'.$end_time)->setSecond(59)->format('Y-m-d H:i:s');
                }

                return Carbon::parse($end);
            }

            return null;
        }

        if (! $start_time) {
            return null;
        }

        if (! $now) {
            $now = Carbon::now();
        } else {
            $now = $now->clone();
        }

        $startDate = self::calculateStartDateTime($start, $end, $start_time, $end_time, $repeat, $now);
        if (! $startDate) {
            return null;
        }

        $endDate = Carbon::parse($startDate)->setTimeFromTimeString($end_time);
        if ($endDate->isBefore($startDate)) {
            $endDate->addDay();
        }

        return $endDate->setSecond(59)->format('Y-m-d H:i:s');
    }

    private static function getDayOfWeek(Carbon $date): int
    {
        $dow = $date->dayOfWeek; // 0 Su, 6 Saturday
        // We want: Mo: 1, Su: 7
        if ($dow === 0) {
            $dow = 7;
        }

        return $dow;
    }

    private static function getYesterdayDayOfWeek(int $dow): int
    {
        $previous_day = 7;
        if ($dow != 1) {
            $previous_day = $dow - 1;
        }

        return $previous_day;
    }
}
