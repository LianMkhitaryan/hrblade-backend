<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    use HasFactory;

    public function getCompetencesAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setCompetencesAttribute($value)
    {
        $this->attributes['competences'] = json_encode(array_values($value));
    }
}
