<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanStripe extends Model
{
    use HasFactory;

    public function getPricesAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setPricesAttribute($value)
    {
        $this->attributes['prices'] = json_encode(array_values($value));
    }
}
