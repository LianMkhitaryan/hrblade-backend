<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Role extends Model
{
    use HasFactory;

    public function toArray()
    {
        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
            return array_merge($this->attributesToArray(), $this->relationsToArray());
        }

        if($this->relationLoaded('questions')) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'questions' => $this->questions,
                'industry_id' => $this->industry_id
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'industry_id' => $this->industry_id
        ];
    }

    public function getNameAttribute($value)
    {
        return $value;
    }

    public function questions()
    {
        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
            return $this->belongsToMany(DefaultQuestion::class,'role_questions','role_id','question_id');
        }
        $locale = App::getLocale();
        return $this->belongsToMany(DefaultQuestion::class,'role_questions','role_id','question_id')->where('language', $locale);
    }

    public function getName()
    {
        return $this->name;
    }

}
