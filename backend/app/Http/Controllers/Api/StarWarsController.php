<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GetFilmByTitleRequest;
use App\Http\Requests\GetPersonByNameRequest;
use App\Models\StarWarsApiLog;
use App\Services\StarWarsApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * This uses the StarWarsApiClient to get Star Wars results. We only use the "result" key from the API response
 * to eliminate unneeded keys such as "social" and "support" that would clutter up the API responses.
 */
class StarWarsController extends Controller
{
    /**
     * @param StarWarsApiClient $starWarsApiClient
     */
    public function __construct(private readonly StarWarsApiClient $starWarsApiClient)
    {}

    /**
     * Execute an API call with logging.
     *
     * @param string $endpoint
     * @param string|null $paramName
     * @param string|null $paramValue
     * @param callable $apiCall
     *
     * @return object|null
     * @throws Throwable
     */
    private function executeWithLogging(
        string $endpoint,
        ?string $paramName,
        ?string $paramValue,
        callable $apiCall
    ): ?object {
        $startedAt = now();
        $exception = null;
        $result = null;

        try {
            $result = $apiCall();
        } catch (Throwable $e) {
            $exception = $e->getMessage();
            throw $e;
        } finally {
            $completedAt = now();

            StarWarsApiLog::create([
                'endpoint' => $endpoint,
                'param_name' => $paramName,
                'param_value' => $paramValue,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'duration_ms' => $startedAt->diffInMilliseconds($completedAt),
                'exception' => $exception,
            ]);
        }

        return $result;
    }

    /**
     * Flatten a single result by merging properties into the top level.
     *
     * @param object $item
     * @return array
     */
    private function flattenResult(object $item): array
    {
        $flattened = (array) $item;

        if (isset($flattened['properties'])) {
            $properties = (array) $flattened['properties'];
            unset($flattened['properties']);
            $flattened = array_merge($flattened, $properties);
        }

        return $flattened;
    }

    /**
     * Format results - handles both single objects and arrays of objects.
     *
     * @param mixed $result
     * @return array|null
     */
    private function formatResponse(mixed $result): ?array
    {
        if ($result === null) {
            return null;
        }

        // Single object with properties
        if (is_object($result) && isset($result->properties)) {
            return $this->flattenResult($result);
        }

        // Array of objects
        if (is_array($result)) {
            return array_map(fn($item) => $this->flattenResult($item), $result);
        }

        return (array) $result;
    }

    /**
     * Extract film ID from a SWAPI film URL.
     *
     * @param string $filmUrl
     * @return int|null
     */
    private function extractFilmId(string $filmUrl): ?int
    {
        // Extract ID from URL like "https://www.swapi.tech/api/films/1"
        if (preg_match('/\/films\/(\d+)$/', $filmUrl, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Fetch movies from an array of film URLs.
     *
     * @param array $filmUrls
     * @return array
     */
    private function fetchMovies(array $filmUrls): array
    {
        $movies = [];

        foreach ($filmUrls as $filmUrl) {
            $filmId = $this->extractFilmId($filmUrl);

            if ($filmId === null) {
                continue;
            }

            try {
                // Fetch the film without logging (to avoid cluttering logs)
                $filmResponse = $this->starWarsApiClient->get("films/$filmId");

                if (isset($filmResponse->result->properties->title)) {
                    $movies[] = [
                        'id' => $filmId,
                        'title' => $filmResponse->result->properties->title,
                    ];
                }
            } catch (Throwable $e) {
                // If we can't fetch a film, just skip it
                continue;
            }
        }

        return $movies;
    }

    /**
     * Format person response with enhanced film information.
     *
     * @param array $person
     * @return array
     */
    private function formatPersonResponse(array $person): array
    {
        // Check if films array exists
        if (isset($person['films']) && is_array($person['films'])) {
            $person['movies'] = $this->fetchMovies($person['films']);
            // Remove the original films URLs array
            unset($person['films']);
        }

        return $person;
    }

    /**
     * Get Star Wars character via LIKE name API query
     *
     * @param GetPersonByNameRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function getPersonByName(GetPersonByNameRequest $request): JsonResponse
    {
        $name = $request->input('name');

        $res = $this->executeWithLogging(
            'people',
            'name',
            $name,
            fn () => $this->starWarsApiClient->get('people', ['name' => $name])
        );

        $formatted = $this->formatResponse($res->result ?? []);

        return response()->json($formatted);
    }

    /**
     * Get Star Wars character via ID
     *
     * @param int $id
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function getPersonById(int $id): JsonResponse
    {
        $res = $this->executeWithLogging(
            "people/$id",
            null,
            null,
            fn () => $this->starWarsApiClient->get("people/$id")
        );

        $formatted = $this->formatResponse($res->result ?? null);

        if ($formatted !== null) {
            $formatted = $this->formatPersonResponse($formatted);
        }

        return response()->json($formatted);
    }

    /**
     * Get Star Wars film via LIKE title API query
     *
     * @param GetFilmByTitleRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function getFilmByTitle(GetFilmByTitleRequest $request): JsonResponse
    {
        $title = $request->input('title');

        $res = $this->executeWithLogging(
            'films',
            'title',
            $title,
            fn () => $this->starWarsApiClient->get('films', ['title' => $title])
        );

        $formatted = $this->formatResponse($res->result ?? []);

        return response()->json($formatted);
    }

    /**
     * Get Star Wars film via ID
     *
     * @param int $id
     *
     * @return JsonResponse
     * @throws Throwable
     */
    /**
     * Get Star Wars film via ID
     *
     * @param int $id
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function getFilmById(int $id): JsonResponse
    {
        $res = $this->executeWithLogging(
            "films/$id",
            null,
            null,
            fn () => $this->starWarsApiClient->get("films/$id")
        );

        $formatted = $this->formatResponse($res->result ?? null);

        if ($formatted !== null) {
            $formatted = $this->formatFilmResponse($formatted);
        }

        return response()->json($formatted);
    }

    /**
     * @return JsonResponse
     */
    public function getApiStats(): JsonResponse
    {
        $res = Cache::get('star_wars_api_stats');

        return response()->json($res);
    }

    /**
     * Extract person ID from a SWAPI person URL.
     *
     * @param string $personUrl
     * @return int|null
     */
    private function extractPersonId(string $personUrl): ?int
    {
        // Extract ID from URL like "https://www.swapi.tech/api/people/1"
        if (preg_match('/\/people\/(\d+)$/', $personUrl, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Fetch characters from an array of person URLs.
     *
     * @param array $personUrls
     * @return array
     */
    private function fetchCharacters(array $personUrls): array
    {
        $characters = [];

        foreach ($personUrls as $personUrl) {
            $personId = $this->extractPersonId($personUrl);

            if ($personId === null) {
                continue;
            }

            try {
                // Fetch the person without logging (to avoid cluttering logs)
                $personResponse = $this->starWarsApiClient->get("people/$personId");

                if (isset($personResponse->result->properties->name)) {
                    $characters[] = [
                        'id' => $personId,
                        'name' => $personResponse->result->properties->name,
                    ];
                }
            } catch (Throwable $e) {
                // If we can't fetch a character, just skip it
                continue;
            }
        }

        return $characters;
    }

    /**
     * Format film response with enhanced character information.
     *
     * @param array $film
     * @return array
     */
    private function formatFilmResponse(array $film): array
    {
        // Check if characters array exists
        if (isset($film['characters']) && is_array($film['characters'])) {
            $film['characters'] = $this->fetchCharacters($film['characters']);
        }

        return $film;
    }
}
