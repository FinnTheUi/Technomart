<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stall extends Model
{
    protected $fillable = [
        'manager_id', 'name', 'location', 'size', 'price', 'status',
        'floor_x', 'floor_y', 'floor_label', 'location_id',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function waitlists()
    {
        return $this->hasMany(Waitlist::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function locationBranch()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
