<?php

namespace Tests\Feature;

use App\Jobs\CalculateStarWarsApiStats;
use App\Models\StarWarsApiLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StarWarsApiStatsTest extends TestCase
{
    use DatabaseMigrations;

    protected array $T;
    protected Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::create(2026, 1, 11, 15, 30, 0);
        Carbon::setTestNow($this->now);
    }


    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calculates_all_time_stats_correctly(): void
    {
        // Create test data
        $this->createTestLogs();

        // Run the job
        $job = new CalculateStarWarsApiStats();
        $job->handle();

        // Get cached stats
        $stats = Cache::get('star_wars_api_stats');

        $this->assertNotNull($stats);
        $this->assertArrayHasKey('all_time', $stats);

        $allTime = $stats['all_time'];

        // Test grand total
        $this->assertEquals(20, $allTime['grand_total']);

        // Test top five queries
        $this->assertCount(5, $allTime['top_five_queries']);
        $this->assertEquals('people/1', $allTime['top_five_queries'][0]['query']);
        $this->assertEquals(5, $allTime['top_five_queries'][0]['count']);
        $this->assertEquals(25.0, $allTime['top_five_queries'][0]['percentage']);

        // Test average duration
        $this->assertEquals(158.0, $allTime['average_duration_ms']);

        // Test most popular hour (15:00 / 3 PM has 10 logs)
        $this->assertEquals(15, $allTime['most_popular_hour']);

        // Test most popular day (Sunday has 12 logs)
        $this->assertEquals('Sunday', $allTime['most_popular_day_of_week']);

        // Test longest/shortest query time
        $this->assertEquals(500, $allTime['longest_query_ms']);
        $this->assertEquals(10, $allTime['shortest_query_ms']);

        // Test total by endpoint
        $this->assertEquals(5, $allTime['total_by_endpoint']['person_by_id']);
        $this->assertEquals(6, $allTime['total_by_endpoint']['person_by_name']);
        $this->assertEquals(5, $allTime['total_by_endpoint']['film_by_id']);
        $this->assertEquals(4, $allTime['total_by_endpoint']['film_by_name']);

        // Test average by endpoint
        $this->assertEquals(100.0, $allTime['average_by_endpoint']['person_by_id']);
        $this->assertEquals(126.67, $allTime['average_by_endpoint']['person_by_name']);
        $this->assertEquals(260.0, $allTime['average_by_endpoint']['film_by_id']);
        $this->assertEquals(150.0, $allTime['average_by_endpoint']['film_by_name']);
    }

    public function test_calculates_last_30_days_stats_correctly(): void
    {
        $this->createTestLogs();

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');
        $last30Days = $stats['last_30_days'];

        // Should include logs from last 20 days (not the 40-day-old ones)
        $this->assertEquals(16, $last30Days['grand_total']);
    }

    public function test_calculates_last_7_days_stats_correctly(): void
    {
        $this->createTestLogs();

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');
        $last7Days = $stats['last_7_days'];

        // Should include logs from last 5 days (not the 20 or 40-day-old ones)
        $this->assertEquals(12, $last7Days['grand_total']);
    }

    public function test_calculates_last_24_hours_stats_correctly(): void
    {
        $this->createTestLogs();

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');
        $last24Hours = $stats['last_24_hours'];

        // Should only include logs from today
        $this->assertEquals(10, $last24Hours['grand_total']);
        $this->assertEquals(15, $last24Hours['most_popular_hour']);
    }

    public function test_handles_empty_database_gracefully(): void
    {
        // Don't create any logs

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');
        $allTime = $stats['all_time'];

        $this->assertEquals(0, $allTime['grand_total']);
        $this->assertEquals(0, $allTime['average_duration_ms']);
        $this->assertEmpty($allTime['top_five_queries']);
        $this->assertNull($allTime['most_popular_hour']);
        $this->assertNull($allTime['most_popular_day_of_week']);
    }

    public function test_query_formatting_includes_params(): void
    {
        // Create logs with params
        StarWarsApiLog::create([
            'endpoint' => 'people',
            'param_name' => 'name',
            'param_value' => 'Luke',
            'started_at' => now(),
            'completed_at' => now(),
            'duration_ms' => 100,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'films',
            'param_name' => 'title',
            'param_value' => 'Empire',
            'started_at' => now(),
            'completed_at' => now(),
            'duration_ms' => 100,
        ]);

        // Create logs without params (ID-based)
        StarWarsApiLog::create([
            'endpoint' => 'people/1',
            'param_name' => null,
            'param_value' => null,
            'started_at' => now(),
            'completed_at' => now(),
            'duration_ms' => 100,
        ]);

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');
        $queries = collect($stats['all_time']['top_five_queries'])->pluck('query')->toArray();

        $this->assertContains('people?name=Luke', $queries);
        $this->assertContains('films?title=Empire', $queries);
        $this->assertContains('people/1', $queries);
    }

    public function test_calculates_percentages_correctly(): void
    {
        // Create 100 logs total for easy percentage math
        for ($i = 1; $i <= 50; $i++) {
            StarWarsApiLog::create([
                'endpoint' => 'people/1',
                'param_name' => null,
                'param_value' => null,
                'started_at' => now(),
                'completed_at' => now(),
                'duration_ms' => 100,
            ]);
        }

        for ($i = 1; $i <= 30; $i++) {
            StarWarsApiLog::create([
                'endpoint' => 'films/1',
                'param_name' => null,
                'param_value' => null,
                'started_at' => now(),
                'completed_at' => now(),
                'duration_ms' => 100,
            ]);
        }

        for ($i = 1; $i <= 20; $i++) {
            StarWarsApiLog::create([
                'endpoint' => 'people',
                'param_name' => 'name',
                'param_value' => 'Luke',
                'started_at' => now(),
                'completed_at' => now(),
                'duration_ms' => 100,
            ]);
        }

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');
        $topQueries = $stats['all_time']['top_five_queries'];

        $this->assertEquals(50.0, $topQueries[0]['percentage']); // 50/100
        $this->assertEquals(30.0, $topQueries[1]['percentage']); // 30/100
        $this->assertEquals(20.0, $topQueries[2]['percentage']); // 20/100
    }

    public function test_artisan_command_dispatches_job(): void
    {
        $this->createTestLogs();

        // Run the artisan command
        $this->artisan('stats:calculate-star-wars-api')
            ->expectsOutput('Calculating Star Wars API statistics...')
            ->expectsOutput('Job dispatched successfully!')
            ->assertExitCode(0);

        // Since we're using sync queue in tests, the job should have run
        $stats = Cache::get('star_wars_api_stats');
        $this->assertNotNull($stats);
    }

    public function test_stats_include_generation_timestamp(): void
    {
        $this->createTestLogs();

        $job = new CalculateStarWarsApiStats();
        $job->handle();

        $stats = Cache::get('star_wars_api_stats');

        $this->assertArrayHasKey('generated_at', $stats);
        $this->assertNotEmpty($stats['generated_at']);

        // Verify it's a valid ISO 8601 timestamp
        $timestamp = Carbon::parse($stats['generated_at']);
        $this->assertInstanceOf(Carbon::class, $timestamp);
    }

    /**
     * Create a diverse set of test logs across different time periods
     */
    private function createTestLogs(): void
    {
        $now = Carbon::now(); // Frozen in setUp(): 2026-01-11 15:30:00 (Sunday)

        // --------------------------------------------------
        // Explicit duration anchors (single source of truth)
        // --------------------------------------------------
        $T = [
            'today_base'   => $now->copy()->subMinutes(30),        // safely within 24h
            'outside_24h'  => $now->copy()->subHours(25),          // intentionally >24h
            'days_20'      => $now->copy()->subDays(20),
            'days_40'      => $now->copy()->subDays(40),
        ];

        // === TODAY (within last 24 hours) - 10 logs ===
        for ($i = 0; $i < 5; $i++) {
            StarWarsApiLog::create([
                'endpoint' => 'people/1',
                'param_name' => null,
                'param_value' => null,
                'started_at' => $T['today_base']->copy()->addMinutes($i),
                'completed_at' => $T['today_base']->copy()->addMinutes($i),
                'duration_ms' => 100,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            StarWarsApiLog::create([
                'endpoint' => 'people',
                'param_name' => 'name',
                'param_value' => 'Luke',
                'started_at' => $T['today_base']->copy()->addMinutes(10 + $i),
                'completed_at' => $T['today_base']->copy()->addMinutes(10 + $i),
                'duration_ms' => 150,
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            StarWarsApiLog::create([
                'endpoint' => 'films/1',
                'param_name' => null,
                'param_value' => null,
                'started_at' => $T['today_base']->copy()->addMinutes(20 + $i),
                'completed_at' => $T['today_base']->copy()->addMinutes(20 + $i),
                'duration_ms' => 200,
            ]);
        }

        // === OUTSIDE LAST 24 HOURS BUT INSIDE 7 DAYS - 2 logs ===
        $olderThan24h = $T['outside_24h'];

        StarWarsApiLog::create([
            'endpoint' => 'films/2',
            'param_name' => null,
            'param_value' => null,
            'started_at' => $olderThan24h,
            'completed_at' => $olderThan24h,
            'duration_ms' => 500, // Longest query
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'people',
            'param_name' => 'name',
            'param_value' => 'Leia',
            'started_at' => $olderThan24h->copy()->addMinutes(5),
            'completed_at' => $olderThan24h->copy()->addMinutes(5),
            'duration_ms' => 10, // Shortest query
        ]);

        // === 20 DAYS AGO - 4 logs ===
        $twentyDaysAgo = $T['days_20'];

        StarWarsApiLog::create([
            'endpoint' => 'films/3',
            'param_name' => null,
            'param_value' => null,
            'started_at' => $twentyDaysAgo,
            'completed_at' => $twentyDaysAgo,
            'duration_ms' => 200,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'films',
            'param_name' => 'title',
            'param_value' => 'Return',
            'started_at' => $twentyDaysAgo->copy()->addMinutes(5),
            'completed_at' => $twentyDaysAgo->copy()->addMinutes(5),
            'duration_ms' => 150,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'films',
            'param_name' => 'title',
            'param_value' => 'Empire',
            'started_at' => $twentyDaysAgo->copy()->addMinutes(10),
            'completed_at' => $twentyDaysAgo->copy()->addMinutes(10),
            'duration_ms' => 150,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'people',
            'param_name' => 'name',
            'param_value' => 'Vader',
            'started_at' => $twentyDaysAgo->copy()->addMinutes(15),
            'completed_at' => $twentyDaysAgo->copy()->addMinutes(15),
            'duration_ms' => 150,
        ]);

        // === 40 DAYS AGO - 4 logs ===
        $fortyDaysAgo = $T['days_40'];

        StarWarsApiLog::create([
            'endpoint' => 'films/4',
            'param_name' => null,
            'param_value' => null,
            'started_at' => $fortyDaysAgo,
            'completed_at' => $fortyDaysAgo,
            'duration_ms' => 200,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'films',
            'param_name' => 'title',
            'param_value' => 'Phantom',
            'started_at' => $fortyDaysAgo->copy()->addMinutes(5),
            'completed_at' => $fortyDaysAgo->copy()->addMinutes(5),
            'duration_ms' => 150,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'films',
            'param_name' => 'title',
            'param_value' => 'Clones',
            'started_at' => $fortyDaysAgo->copy()->addMinutes(10),
            'completed_at' => $fortyDaysAgo->copy()->addMinutes(10),
            'duration_ms' => 150,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'people',
            'param_name' => 'name',
            'param_value' => 'Yoda',
            'started_at' => $fortyDaysAgo->copy()->addMinutes(15),
            'completed_at' => $fortyDaysAgo->copy()->addMinutes(15),
            'duration_ms' => 150,
        ]);

        // --------------------------------------------------
        // Totals & windows are now ACTUALLY correct:
        //
        // Last 24 hours: 10
        // Last 7 days:   12
        // Last 30 days:  16
        // All time:      20
        // --------------------------------------------------
    }
}
