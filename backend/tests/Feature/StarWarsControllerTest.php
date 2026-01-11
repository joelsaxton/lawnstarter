<?php

namespace Tests\Feature;

use App\Jobs\CalculateStarWarsApiStats;
use App\Models\StarWarsApiLog;
use App\Services\StarWarsApiClient;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class StarWarsControllerTest extends TestCase
{
    private function mockStarWarsApiClient(string $method, string $endpoint, ?array $params, array $response): void
    {
        $this->instance(
            StarWarsApiClient::class,
            Mockery::mock(StarWarsApiClient::class, function (MockInterface $mock) use ($method, $endpoint, $params, $response) {
                $expectation = $mock->shouldReceive($method);

                if ($params !== null) {
                    $expectation->with($endpoint, $params);
                } else {
                    $expectation->with($endpoint);
                }

                $expectation->once()
                    ->andReturn(json_decode(json_encode($response)));
            })
        );
    }

    public function test_get_person_by_id_returns_single_person(): void
    {
        $apiResponse = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'created' => '2026-01-10T21:28:46.102Z',
                    'edited' => '2026-01-10T21:28:46.102Z',
                    'name' => 'Wilhuff Tarkin',
                    'gender' => 'male',
                    'skin_color' => 'fair',
                    'hair_color' => 'auburn, grey',
                    'height' => '180',
                    'eye_color' => 'blue',
                    'mass' => 'unknown',
                    'homeworld' => 'https://www.swapi.tech/api/planets/21',
                    'birth_year' => '64BBY',
                    'vehicles' => [],
                    'starships' => [],
                    'films' => [
                        'https://www.swapi.tech/api/films/1',
                        'https://www.swapi.tech/api/films/6',
                    ],
                    'url' => 'https://www.swapi.tech/api/people/12',
                ],
                '_id' => '5f63a36eee9fd7000499be4d',
                'description' => 'A person within the Star Wars universe',
                'uid' => '12',
                '__v' => 4,
            ],
        ];

        $this->mockStarWarsApiClient('get', 'people/12', null, $apiResponse);

        $response = $this->getJson(route('starwars.person.id', ['id' => 12]));

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Wilhuff Tarkin')
            ->assertJsonPath('gender', 'male')
            ->assertJsonPath('birth_year', '64BBY')
            ->assertJsonPath('uid', '12');

        // Verify API log was created
        $this->assertDatabaseCount('star_wars_api_logs', 1);

        $log = StarWarsApiLog::first();
        $this->assertEquals('people/12', $log->endpoint);
        $this->assertNull($log->param_name);
        $this->assertNull($log->param_value);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
        $this->assertNull($log->exception);
    }

    public function test_get_person_by_name_returns_multiple_people(): void
    {
        $apiResponse = [
            'message' => 'ok',
            'result' => [
                [
                    'properties' => [
                        'name' => 'Leia Organa',
                        'gender' => 'female',
                        'skin_color' => 'light',
                        'hair_color' => 'brown',
                        'height' => '150',
                        'eye_color' => 'brown',
                        'mass' => '49',
                        'homeworld' => 'https://www.swapi.tech/api/planets/2',
                        'birth_year' => '19BBY',
                        'url' => 'https://www.swapi.tech/api/people/5',
                    ],
                    'uid' => '5',
                ],
                [
                    'properties' => [
                        'name' => 'Wedge Antilles',
                        'gender' => 'male',
                        'skin_color' => 'fair',
                        'hair_color' => 'brown',
                        'height' => '170',
                        'eye_color' => 'hazel',
                        'mass' => '77',
                        'homeworld' => 'https://www.swapi.tech/api/planets/22',
                        'birth_year' => '21BBY',
                        'url' => 'https://www.swapi.tech/api/people/18',
                    ],
                    'uid' => '18',
                ],
                [
                    'properties' => [
                        'name' => 'Poggle the Lesser',
                        'gender' => 'male',
                        'url' => 'https://www.swapi.tech/api/people/63',
                    ],
                    'uid' => '63',
                ],
                [
                    'properties' => [
                        'name' => 'Raymus Antilles',
                        'gender' => 'male',
                        'url' => 'https://www.swapi.tech/api/people/81',
                    ],
                    'uid' => '81',
                ],
            ],
        ];

        $this->mockStarWarsApiClient('get', 'people', ['name' => 'Le'], $apiResponse);

        $response = $this->getJson(route('starwars.person.name', ['name' => 'Le']));

        $response->assertStatus(200)
            ->assertJsonCount(4)
            ->assertJsonPath('0.name', 'Leia Organa')
            ->assertJsonPath('1.name', 'Wedge Antilles')
            ->assertJsonPath('2.name', 'Poggle the Lesser')
            ->assertJsonPath('3.name', 'Raymus Antilles');

        // Verify API log was created
        $this->assertDatabaseCount('star_wars_api_logs', 1);

        $log = StarWarsApiLog::first();
        $this->assertEquals('people', $log->endpoint);
        $this->assertEquals('name', $log->param_name);
        $this->assertEquals('Le', $log->param_value);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
        $this->assertNull($log->exception);
    }

    public function test_get_person_by_name_requires_name_parameter(): void
    {
        $response = $this->getJson(route('starwars.person.name'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Verify no API log was created for validation failure
        $this->assertDatabaseCount('star_wars_api_logs', 0);
    }

    public function test_get_film_by_id_returns_single_film(): void
    {
        $apiResponse = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'created' => '2026-01-10T21:28:46.106Z',
                    'edited' => '2026-01-10T21:28:46.106Z',
                    'producer' => 'Gary Kurtz, Rick McCallum',
                    'title' => 'A New Hope',
                    'episode_id' => 4,
                    'director' => 'George Lucas',
                    'release_date' => '1977-05-25',
                    'opening_crawl' => 'It is a period of civil war...',
                    'characters' => [
                        'https://www.swapi.tech/api/people/1',
                        'https://www.swapi.tech/api/people/2',
                    ],
                    'planets' => [
                        'https://www.swapi.tech/api/planets/1',
                    ],
                    'starships' => [
                        'https://www.swapi.tech/api/starships/2',
                    ],
                    'vehicles' => [
                        'https://www.swapi.tech/api/vehicles/4',
                    ],
                    'species' => [
                        'https://www.swapi.tech/api/species/1',
                    ],
                    'url' => 'https://www.swapi.tech/api/films/1',
                ],
                '_id' => '5f63a117cf50d100047f9762',
                'description' => 'A Star Wars Film',
                'uid' => '1',
                '__v' => 2,
            ],
        ];

        $this->mockStarWarsApiClient('get', 'films/1', null, $apiResponse);

        $response = $this->getJson(route('starwars.film.id', ['id' => 1]));

        $response->assertStatus(200)
            ->assertJsonPath('title', 'A New Hope')
            ->assertJsonPath('episode_id', 4)
            ->assertJsonPath('director', 'George Lucas')
            ->assertJsonPath('release_date', '1977-05-25')
            ->assertJsonPath('uid', '1');

        // Verify API log was created
        $this->assertDatabaseCount('star_wars_api_logs', 1);

        $log = StarWarsApiLog::first();
        $this->assertEquals('films/1', $log->endpoint);
        $this->assertNull($log->param_name);
        $this->assertNull($log->param_value);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
        $this->assertNull($log->exception);
    }

    public function test_get_film_by_title_returns_multiple_films(): void
    {
        $apiResponse = [
            'message' => 'ok',
            'result' => [
                [
                    'properties' => [
                        'title' => 'The Empire Strikes Back',
                        'episode_id' => 5,
                        'director' => 'Irvin Kershner',
                        'release_date' => '1980-05-17',
                        'url' => 'https://www.swapi.tech/api/films/2',
                    ],
                    'uid' => '2',
                ],
                [
                    'properties' => [
                        'title' => 'Return of the Jedi',
                        'episode_id' => 6,
                        'director' => 'Richard Marquand',
                        'release_date' => '1983-05-25',
                        'url' => 'https://www.swapi.tech/api/films/3',
                    ],
                    'uid' => '3',
                ],
                [
                    'properties' => [
                        'title' => 'Revenge of the Sith',
                        'episode_id' => 3,
                        'director' => 'George Lucas',
                        'release_date' => '2005-05-19',
                        'url' => 'https://www.swapi.tech/api/films/6',
                    ],
                    'uid' => '6',
                ],
            ],
        ];

        $this->mockStarWarsApiClient('get', 'films', ['title' => 'Re'], $apiResponse);

        $response = $this->getJson(route('starwars.film.title', ['title' => 'Re']));

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonPath('0.title', 'The Empire Strikes Back')
            ->assertJsonPath('1.title', 'Return of the Jedi')
            ->assertJsonPath('2.title', 'Revenge of the Sith');

        // Verify API log was created
        $this->assertDatabaseCount('star_wars_api_logs', 1);

        $log = StarWarsApiLog::first();
        $this->assertEquals('films', $log->endpoint);
        $this->assertEquals('title', $log->param_name);
        $this->assertEquals('Re', $log->param_value);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
        $this->assertNull($log->exception);
    }

    public function test_get_film_by_title_requires_title_parameter(): void
    {
        $response = $this->getJson(route('starwars.film.title'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        // Verify no API log was created for validation failure
        $this->assertDatabaseCount('star_wars_api_logs', 0);
    }

    public function test_get_api_stats_returns_cached_stats(): void
    {
        // Create some test logs
        StarWarsApiLog::create([
            'endpoint' => 'people/1',
            'param_name' => null,
            'param_value' => null,
            'started_at' => now(),
            'completed_at' => now(),
            'duration_ms' => 100,
        ]);

        StarWarsApiLog::create([
            'endpoint' => 'films/1',
            'param_name' => null,
            'param_value' => null,
            'started_at' => now(),
            'completed_at' => now(),
            'duration_ms' => 200,
        ]);

        // Manually run the stats calculation job
        $job = new CalculateStarWarsApiStats();
        $job->handle();

        // Call the stats endpoint
        $response = $this->getJson(route('starwars.api.stats'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'all_time' => [
                    'top_five_queries',
                    'average_duration_ms',
                    'most_popular_hour',
                    'most_popular_day_of_week',
                    'longest_query_ms',
                    'shortest_query_ms',
                    'average_by_endpoint',
                    'total_by_endpoint',
                    'grand_total',
                ],
                'last_30_days',
                'last_7_days',
                'last_24_hours',
                'generated_at',
            ]);

        // Verify the grand total matches our test data
        $response->assertJsonPath('all_time.grand_total', 2);
    }

    public function test_get_api_stats_returns_empty_array_when_no_stats_cached(): void
    {
        // Don't generate any stats, just call the endpoint
        $response = $this->getJson(route('starwars.api.stats'));

        $response->assertStatus(200)
            ->assertJson([]);
    }
    public function test_get_person_by_id_includes_movies(): void
    {
        $personResponse = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'name' => 'Luke Skywalker',
                    'birth_year' => '19BBY',
                    'gender' => 'male',
                    'films' => [
                        'https://www.swapi.tech/api/films/1',
                        'https://www.swapi.tech/api/films/2',
                    ],
                ],
                'uid' => '1',
            ],
        ];

        $film1Response = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'title' => 'A New Hope',
                ],
            ],
        ];

        $film2Response = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'title' => 'The Empire Strikes Back',
                ],
            ],
        ];

        // Mock the person call
        $this->instance(
            StarWarsApiClient::class,
            Mockery::mock(StarWarsApiClient::class, function (MockInterface $mock) use ($personResponse, $film1Response, $film2Response) {
                // First call for the person
                $mock->shouldReceive('get')
                    ->with('people/1')
                    ->once()
                    ->andReturn(json_decode(json_encode($personResponse)));

                // Second call for film 1
                $mock->shouldReceive('get')
                    ->with('films/1')
                    ->once()
                    ->andReturn(json_decode(json_encode($film1Response)));

                // Third call for film 2
                $mock->shouldReceive('get')
                    ->with('films/2')
                    ->once()
                    ->andReturn(json_decode(json_encode($film2Response)));
            })
        );

        $response = $this->getJson(route('starwars.person.id', ['id' => 1]));

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Luke Skywalker')
            ->assertJsonPath('movies.0.id', 1)
            ->assertJsonPath('movies.0.title', 'A New Hope')
            ->assertJsonPath('movies.1.id', 2)
            ->assertJsonPath('movies.1.title', 'The Empire Strikes Back')
            ->assertJsonCount(2, 'movies')
            ->assertJsonMissing(['films']); // Ensure films array is removed
    }
    
    public function test_get_film_by_id_includes_characters(): void
    {
        $filmResponse = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'title' => 'A New Hope',
                    'opening_crawl' => 'It is a period of civil war...',
                    'director' => 'George Lucas',
                    'characters' => [
                        'https://www.swapi.tech/api/people/1',
                        'https://www.swapi.tech/api/people/5',
                    ],
                ],
                'uid' => '1',
            ],
        ];

        $person1Response = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'name' => 'Luke Skywalker',
                ],
            ],
        ];

        $person2Response = [
            'message' => 'ok',
            'result' => [
                'properties' => [
                    'name' => 'Leia Organa',
                ],
            ],
        ];

        // Mock the film call
        $this->instance(
            StarWarsApiClient::class,
            Mockery::mock(StarWarsApiClient::class, function (MockInterface $mock) use ($filmResponse, $person1Response, $person2Response) {
                // First call for the film
                $mock->shouldReceive('get')
                    ->with('films/1')
                    ->once()
                    ->andReturn(json_decode(json_encode($filmResponse)));

                // Second call for person 1
                $mock->shouldReceive('get')
                    ->with('people/1')
                    ->once()
                    ->andReturn(json_decode(json_encode($person1Response)));

                // Third call for person 5
                $mock->shouldReceive('get')
                    ->with('people/5')
                    ->once()
                    ->andReturn(json_decode(json_encode($person2Response)));
            })
        );

        $response = $this->getJson(route('starwars.film.id', ['id' => 1]));

        $response->assertStatus(200)
            ->assertJsonPath('title', 'A New Hope')
            ->assertJsonPath('characters.0.id', 1)
            ->assertJsonPath('characters.0.name', 'Luke Skywalker')
            ->assertJsonPath('characters.1.id', 5)
            ->assertJsonPath('characters.1.name', 'Leia Organa')
            ->assertJsonCount(2, 'characters');
    }
}
