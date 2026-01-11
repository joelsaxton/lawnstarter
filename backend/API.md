# Star Wars API Documentation

Base URL: `http://localhost/api` (development)

## Table of Contents

- [Health Check](#health-check)
- [Person Endpoints](#person-endpoints)
- [Film Endpoints](#film-endpoints)
- [Statistics Endpoint](#statistics-endpoint)

---

## Health Check

### GET /health

Check if the API is running and responsive.

**Response:**

```json
{
    "status": "ok",
    "timestamp": "2026-01-11T18:30:00+00:00"
}
```

**Status Codes:**

- `200 OK` - API is healthy

---

## Person Endpoints

### GET /starwars/person?name={query}

Search for Star Wars characters by name (partial match).

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| name | string | Yes | Partial or full name to search for |

**Example Request:**

```bash
GET /api/starwars/person?name=luke
```

**Example Response:**

```json
[
    {
        "uid": "1",
        "name": "Luke Skywalker",
        "gender": "male",
        "birth_year": "19BBY",
        "height": "172",
        "mass": "77",
        "hair_color": "blond",
        "skin_color": "fair",
        "eye_color": "blue",
        "homeworld": "https://www.swapi.tech/api/planets/1",
        "url": "https://www.swapi.tech/api/people/1"
    }
]
```

**Status Codes:**

- `200 OK` - Success (returns array, empty if no matches)
- `422 Unprocessable Entity` - Missing or invalid `name` parameter

---

### GET /starwars/person/{id}

Get detailed information about a specific character by ID, including their movies.

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| id | integer | Yes | Character ID (path parameter) |

**Example Request:**

```bash
GET /api/starwars/person/1
```

**Example Response:**

```json
{
    "uid": "1",
    "name": "Luke Skywalker",
    "gender": "male",
    "birth_year": "19BBY",
    "height": "172",
    "mass": "77",
    "hair_color": "blond",
    "skin_color": "fair",
    "eye_color": "blue",
    "homeworld": "https://www.swapi.tech/api/planets/1",
    "movies": [
        {
            "id": 1,
            "title": "A New Hope"
        },
        {
            "id": 2,
            "title": "The Empire Strikes Back"
        }
    ]
}
```

**Notes:**

- The `movies` array contains enriched data with movie IDs and titles
- The original `films` URLs array is removed from the response

**Status Codes:**

- `200 OK` - Success
- `404 Not Found` - Character not found

---

## Film Endpoints

### GET /starwars/film?title={query}

Search for Star Wars films by title (partial match).

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| title | string | Yes | Partial or full title to search for |

**Example Request:**

```bash
GET /api/starwars/film?title=empire
```

**Example Response:**

```json
[
    {
        "uid": "2",
        "title": "The Empire Strikes Back",
        "episode_id": 5,
        "director": "Irvin Kershner",
        "producer": "Gary Kurtz, Rick McCallum",
        "release_date": "1980-05-17",
        "url": "https://www.swapi.tech/api/films/2"
    }
]
```

**Status Codes:**

- `200 OK` - Success (returns array, empty if no matches)
- `422 Unprocessable Entity` - Missing or invalid `title` parameter

---

### GET /starwars/film/{id}

Get detailed information about a specific film by ID, including its characters.

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| id | integer | Yes | Film ID (path parameter) |

**Example Request:**

```bash
GET /api/starwars/film/1
```

**Example Response:**

```json
{
    "uid": "1",
    "title": "A New Hope",
    "episode_id": 4,
    "director": "George Lucas",
    "producer": "Gary Kurtz, Rick McCallum",
    "release_date": "1977-05-25",
    "opening_crawl": "It is a period of civil war...",
    "characters": [
        {
            "id": 1,
            "name": "Luke Skywalker"
        },
        {
            "id": 5,
            "name": "Leia Organa"
        }
    ]
}
```

**Notes:**

- The `characters` array contains enriched data with character IDs and names
- The original `characters` URLs array is replaced with enriched data
- The `opening_crawl` contains the film's iconic opening text

**Status Codes:**

- `200 OK` - Success
- `404 Not Found` - Film not found

---

## Statistics Endpoint

### GET /starwars/stats

Get comprehensive statistics about API usage, updated every 5 minutes.

**Example Request:**

```bash
GET /api/starwars/stats
```

**Example Response:**

```json
{
    "all_time": {
        "top_five_queries": [
            {
                "query": "people/1",
                "count": 45,
                "percentage": 34.5
            },
            {
                "query": "people/2",
                "count": 23,
                "percentage": 25.5
            },
            {
                "query": "films/1",
                "count": 23,
                "percentage": 25.5
            },
            {
                "query": "people?name=Luke",
                "count": 12,
                "percentage": 15.5
            },
            {
                "query": "people/8",
                "count": 6,
                "percentage": 7.5
            }
        ],
        "average_duration_ms": 158.3,
        "most_popular_hour": 15,
        "most_popular_day_of_week": "Saturday",
        "longest_query_ms": 1250,
        "shortest_query_ms": 23,
        "average_by_endpoint": {
            "person_by_id": 120.5,
            "person_by_name": 145.2,
            "film_by_id": 135.8,
            "film_by_name": 152.3
        },
        "total_by_endpoint": {
            "person_by_id": 120,
            "person_by_name": 85,
            "film_by_id": 95,
            "film_by_name": 70
        },
        "grand_total": 370
    },
    "last_30_days": {
        /* Same structure */
    },
    "last_7_days": {
        /* Same structure */
    },
    "last_24_hours": {
        /* Same structure */
    },
    "generated_at": "2026-01-11T18:30:00+00:00"
}
```

**Statistics Breakdown:**

| Field                      | Description                                                    |
|----------------------------|----------------------------------------------------------------|
| `top_five_queries`         | Most frequently accessed endpoints with counts and percentages |
| `average_duration_ms`      | Average API response time in milliseconds                      |
| `most_popular_hour`        | Hour of day (0-23) with highest API usage                      |
| `most_popular_day_of_week` | Day name with highest API usage                                |
| `longest_query_ms`         | Slowest API response time recorded                             |
| `shortest_query_ms`        | Fastest API response time recorded                             |
| `average_by_endpoint`      | Average response time per endpoint type                        |
| `total_by_endpoint`        | Total request count per endpoint type                          |
| `grand_total`              | Total number of requests                                       |
| `generated_at`             | Timestamp when statistics were last calculated                 |

**Time Windows:**

- `all_time` - All recorded data
- `last_30_days` - Data from the past 30 days
- `last_7_days` - Data from the past 7 days
- `last_24_hours` - Data from the past 24 hours

**Notes:**

- Statistics are cached and regenerated every 5 minutes via a scheduled job
- If no statistics have been generated yet, returns an empty array `[]`

**Status Codes:**

- `200 OK` - Success (may be empty if stats not yet generated)

---

## Data Models

### Person Object

```typescript
{
    uid: string;
    name: string;
    gender: string;
    birth_year: string;
    height: string;        // in centimeters
    mass: string;          // in kilograms
    hair_color: string;
    skin_color: string;
    eye_color: string;
    homeworld: string;     // URL to planet resource
    url: string;           // URL to this person resource
    movies ? : Array<{       // Only in /person/{id} endpoint
        id: number;
        title: string;
    }>;
}
```

### Film Object

```typescript
{
    uid: string;
    title: string;
    episode_id: number;
    director: string;
    producer: string;
    release_date: string;  // YYYY-MM-DD format
    opening_crawl: string;
    url: string;           // URL to this film resource
    characters ? : Array<{   // Only in /film/{id} endpoint
        id: number;
        name: string;
    }>;
}
```

---

## Error Responses

All endpoints may return the following error formats:

### 422 Unprocessable Entity

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": [
            "The name field is required."
        ]
    }
}
```

### 404 Not Found

```json
{
    "message": "Resource not found"
}
```

### 500 Internal Server Error

```json
{
    "message": "Server error"
}
```

---

## Rate Limiting

Currently no rate limiting is implemented. API calls are logged for analytics purposes.

## CORS

CORS is configured to allow requests from the frontend application running on `http://localhost:5173`.

## Authentication

No authentication is currently required for any endpoints.

---

## Notes

- All responses are in JSON format with `Content-Type: application/json`
- All requests should include `Accept: application/json` header
- The API proxies data from [SWAPI (Star Wars API)](https://www.swapi.tech/)
- Response times and availability depend on the upstream SWAPI service
- All API calls are logged for statistics and monitoring
