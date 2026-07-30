<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'stock', 'category', 'image', 'is_active'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
