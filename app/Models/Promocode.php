<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;

    public function getDiscountAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setDiscountAttribute($value)
    {
        $this->attributes['discount'] = json_encode(array_values($value));
    }
}
