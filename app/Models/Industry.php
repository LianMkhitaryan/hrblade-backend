<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Industry extends Model
{
    use HasFactory;

    public function getNameAttribute($value)
    {
        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
            return array_values(json_decode($value, true) ?: []);
        }

        $locale = App::getLocale();
        $texts = array_values(json_decode($value, true) ?: []);
        foreach ($texts as $text) {
            if($text['language'] == $locale) {
                return $text['name'];
            }
        }

        if(count($texts)) {
            return $texts[0]['name'];
        }

        return '';
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = json_encode(array_values($value));
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function getName()
    {
        if(isset($this->getOriginal('name')[0]['name'])) {
            return $this->getOriginal('name')[0]['name'];
        }
        return $this->id;
    }
}
