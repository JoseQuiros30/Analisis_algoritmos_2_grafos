<?php

namespace App\Models;

use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

#[Fillable(['origin_station_id', 'destination_station_id', 'line', 'base_time', 'weather_penalty', 'peak_hour_penalty', 'congestion_penalty', 'transfer_penalty'])]
class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use HasFactory;

    /** @var array<string, int> */
    protected $attributes = [
        'weather_penalty' => 0,
        'peak_hour_penalty' => 0,
        'congestion_penalty' => 0,
        'transfer_penalty' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (Connection $connection): void {
            Validator::make($connection->getAttributes(), [
                'origin_station_id' => ['required', 'integer', 'min:1', 'different:destination_station_id'],
                'destination_station_id' => ['required', 'integer', 'min:1'],
                'line' => ['required', 'string', 'max:20'],
                'base_time' => ['required', 'integer', 'min:1'],
                'weather_penalty' => ['required', 'integer', 'min:0'],
                'peak_hour_penalty' => ['required', 'integer', 'min:0'],
                'congestion_penalty' => ['required', 'integer', 'min:0'],
                'transfer_penalty' => ['required', 'integer', 'min:0'],
            ])->validate();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'base_time' => 'integer',
            'weather_penalty' => 'integer',
            'peak_hour_penalty' => 'integer',
            'congestion_penalty' => 'integer',
            'transfer_penalty' => 'integer',
        ];
    }

    /** @return BelongsTo<Station, $this> */
    public function originStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'origin_station_id');
    }

    /** @return BelongsTo<Station, $this> */
    public function destinationStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'destination_station_id');
    }
}
