<?php

namespace App\Jobs;

use App\Models\StarWarsApiLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Builds redis cached statistical data for the Star Wars API over
 * multiple time periods: all time, 30 days, 7 days and 24 hours
 */
class CalculateStarWarsApiStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_KEY = 'star_wars_api_stats';

    public function handle(): void
    {
        Log::info('CalculateStarWarsApiStats::handle() - Generating Star Wars API Stats');

        $stats = [
            'all_time' => $this->calculateStats(),
            'last_30_days' => $this->calculateStats(Carbon::now()->subDays(30)),
            'last_7_days' => $this->calculateStats(Carbon::now()->subDays(7)),
            'last_24_hours' => $this->calculateStats(Carbon::now()->subHours(24)),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_KEY, $stats);
    }

    private function calculateStats(?Carbon $since = null): array
    {
        $query = StarWarsApiLog::query();

        if ($since) {
            $query->where('started_at', '>=', $since);
        }

        $logs = $query->get();

        if ($logs->isEmpty()) {
            return $this->emptyStats();
        }

        return [
            'top_five_queries' => $this->topFiveQueries($logs),
            'average_duration_ms' => round($logs->avg('duration_ms'), 2),
            'most_popular_hour' => $this->mostPopularHour($logs),
            'most_popular_day_of_week' => $this->mostPopularDayOfWeek($logs),
            'longest_query_ms' => $logs->max('duration_ms'),
            'shortest_query_ms' => $logs->min('duration_ms'),
            'average_by_endpoint' => $this->averageByEndpoint($logs),
            'total_by_endpoint' => $this->totalByEndpoint($logs),
            'grand_total' => $logs->count(),
        ];
    }

    private function emptyStats(): array
    {
        return [
            'top_five_queries' => [],
            'average_duration_ms' => 0,
            'most_popular_hour' => null,
            'most_popular_day_of_week' => null,
            'longest_query_ms' => 0,
            'shortest_query_ms' => 0,
            'average_by_endpoint' => [],
            'total_by_endpoint' => [],
            'grand_total' => 0,
        ];
    }

    private function topFiveQueries($logs): array
    {
        $queries = $logs->map(function ($log) {
            if ($log->param_name && $log->param_value) {
                return "{$log->endpoint}?{$log->param_name}={$log->param_value}";
            }
            return $log->endpoint;
        });

        $counts = $queries->countBy();
        $total = $logs->count();

        return $counts->sortDesc()
            ->take(5)
            ->map(function ($count, $query) use ($total) {
                return [
                    'query' => $query,
                    'count' => $count,
                    'percentage' => round(($count / $total) * 100, 2),
                ];
            })
            ->values()
            ->toArray();
    }

    private function mostPopularHour($logs): ?int
    {
        $hours = $logs->groupBy(function ($log) {
            return $log->started_at->format('H');
        });

        if ($hours->isEmpty()) {
            return null;
        }

        return (int) $hours->sortByDesc(function ($group) {
            return $group->count();
        })->keys()->first();
    }

    private function mostPopularDayOfWeek($logs): ?string
    {
        $days = $logs->groupBy(function ($log) {
            return $log->started_at->format('l'); // Monday, Tuesday, etc.
        });

        if ($days->isEmpty()) {
            return null;
        }

        return $days->sortByDesc(function ($group) {
            return $group->count();
        })->keys()->first();
    }

    private function averageByEndpoint($logs): array
    {
        return [
            'person_by_id' => $this->averageForPattern($logs, 'people/', true),
            'person_by_name' => $this->averageForPattern($logs, 'people', false),
            'film_by_id' => $this->averageForPattern($logs, 'films/', true),
            'film_by_name' => $this->averageForPattern($logs, 'films', false),
        ];
    }

    private function averageForPattern($logs, string $pattern, bool $withId): float
    {
        $filtered = $logs->filter(function ($log) use ($pattern, $withId) {
            if ($withId) {
                // Match "people/12" or "films/1" (with ID in path)
                return str_starts_with($log->endpoint, $pattern) &&
                    strlen($log->endpoint) > strlen($pattern) &&
                    is_null($log->param_name);
            } else {
                // Match "people" or "films" (without ID, with query params)
                return $log->endpoint === rtrim($pattern, '/') &&
                    !is_null($log->param_name);
            }
        });

        if ($filtered->isEmpty()) {
            return 0;
        }

        return round($filtered->avg('duration_ms'), 2);
    }

    private function totalByEndpoint($logs): array
    {
        return [
            'person_by_id' => $this->countForPattern($logs, 'people/', true),
            'person_by_name' => $this->countForPattern($logs, 'people', false),
            'film_by_id' => $this->countForPattern($logs, 'films/', true),
            'film_by_name' => $this->countForPattern($logs, 'films', false),
        ];
    }

    private function countForPattern($logs, string $pattern, bool $withId): int
    {
        return $logs->filter(function ($log) use ($pattern, $withId) {
            if ($withId) {
                return str_starts_with($log->endpoint, $pattern) &&
                    strlen($log->endpoint) > strlen($pattern) &&
                    is_null($log->param_name);
            } else {
                return $log->endpoint === rtrim($pattern, '/') &&
                    !is_null($log->param_name);
            }
        })->count();
    }
}
