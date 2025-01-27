<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class DefaultQuestion extends Model
{
    use HasFactory;

    protected $casts = [
        'keywords' =>'json',
    ];

    public function toArray()
    {
        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
            return array_merge($this->attributesToArray(), $this->relationsToArray());
        }

        return [
            'language' => $this->language,
            'time' => $this->time,
            'id' => $this->id,
            'role_id' => $this->role_id,
            'question' => $this->question,
            'type' => $this->type,
            'name' => $this->name,
            'video' => $this->video,
            'image' => $this->image,
            'category' => $this->category
        ];
    }

    protected $fillable = ['question','type', 'language','time','positive','negative','neutral','is_ai'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_questions', 'question_id', 'role_id');
    }

    public function setVideoAttribute($value)
    {
        $this->attributes['video'] = json_encode(array_values($value));
    }

    public function getVideoAttribute($value)
    {
        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
            return array_values(json_decode($value, true) ?: []);
        }


        $videos = array_values(json_decode($value, true) ?: []);
        foreach ($videos as $video) {
            return Storage::disk('public')->url($video['video']);
        }

        if(count($videos)) {
            return Storage::disk('public')->url($videos[0]['video']);
        }

        return null;
    }

    public function getImageAttribute($value)
    {
        if($value) {
            return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
        }

        return null;
    }

    public function category()
    {
        return $this->belongsTo(DefaultQuestionCategory::class, 'category_id');
    }
}
