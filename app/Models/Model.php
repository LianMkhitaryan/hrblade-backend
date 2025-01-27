<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model as LaravelModel;

class Model extends LaravelModel
{
    protected static function booted()
    {
        static::saving(function ($model) {
            $copy = clone $model;
            $copy->setConnection('mysql_ya');
            $copy->saveQuietly();
        });
    }
}
