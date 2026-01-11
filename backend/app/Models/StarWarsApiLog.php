<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Logs every Star Wars API request made by StarWarsController
 *
 * @property int $id
 * @property string $endpoint
 * @property string|null $param_name
 * @property string|null $param_value
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property int $duration_ms
 * @property string|null $exception
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StarWarsApiLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
    ];
}
