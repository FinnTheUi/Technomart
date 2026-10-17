<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\StallStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stall extends Model
{
    protected $fillable = [
        'manager_id', 'name', 'location', 'size', 'price', 'status',
        'floor_x', 'floor_y', 'floor_label', 'location_id',
        'floor_number', 'stall_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => StallStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function waitlists(): HasMany
    {
        return $this->hasMany(Waitlist::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function locationBranch(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Search by stall name. A bare number ("7" or "#7") matches names ending in exactly
     * that number — "Stall 7" and "Cart 7", not "Stall 17".
     */
    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        if (preg_match('/^#?\s*(\d+)$/', $term, $m)) {
            return $query->where(fn ($q) => $q->where('name', 'like', '% '.(int) $m[1])->orWhere('name', (string) (int) $m[1]));
        }

        // Treat LIKE wildcards typed by the user as literal characters.
        return $query->where('name', 'like', '%'.addcslashes($term, '\\%_').'%');
    }

    public function isAvailable(): bool
    {
        return $this->status === StallStatus::Available;
    }

    public function hasActiveLease(): bool
    {
        return $this->contracts()->where('status', ContractStatus::Active)->exists();
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', ContractStatus::Active)->latestOfMany();
    }

    /**
     * The fields safe to show the public (floor-plan maps, live availability feed).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'floor' => $this->floor_number,
            'number' => $this->stall_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'price' => (float) $this->price,
            'size' => $this->size,
            'location' => $this->location,
        ];
    }
}
