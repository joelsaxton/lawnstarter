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
     * Flatten results - handles both single objects and arrays of objects.
     *
     * @param mixed $result
     * @return array|null
     */
    private function flattenResults(mixed $result): ?array
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

        $flattened = $this->flattenResults($res->result ?? []);

        return response()->json($flattened);
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

        $flattened = $this->flattenResults($res->result ?? null);

        return response()->json($flattened);
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

        $flattened = $this->flattenResults($res->result ?? []);

        return response()->json($flattened);
    }

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

        $flattened = $this->flattenResults($res->result ?? null);

        return response()->json($flattened);
    }

    /**
     * @return JsonResponse
     */
    public function getApiStats(): JsonResponse
    {
        $res = Cache::get('star_wars_api_stats');

        return response()->json($res);
    }
}
