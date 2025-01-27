<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function response()
    {
        return $this->belongsTo(Response::class);
    }
}
