<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Question extends Model
{
    protected $fillable = ['question','time','job_id'];

    protected $hidden = ['en','ru','de','es'];

    use HasFactory, SoftDeletes;

    public $driver;

    protected static function booted()
    {
        static::retrieved(function ($question) {
            $question->driver = $question->isRus() ? env('FILESYSTEM_DRIVER_YA') : env('FILESYSTEM_DRIVER');
        });
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function tests()
    {
        return $this->hasMany(Test::class)->orderBy('sorting');
    }

    public function defaultQuestion()
    {
        return $this->belongsTo(DefaultQuestion::class, 'default_id');
    }

    public function getVideoAttribute($value)
    {
        if($this->video_link) {
            return $this->video_link;
        }
        if($this->video_transcoded) {
            if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists($this->video_transcoded)) {
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->getVisibility($this->video_transcoded) == 'private') {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($this->video_transcoded, 'public');
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($value);
                    $this->video_link = Storage::disk(env('FILESYSTEM_DRIVER'))->url($this->video_transcoded);
                    $this->save();

                    if($this->isRus()) {
                        Storage::disk(env('FILESYSTEM_DRIVER_YA'))->put($this->video_transcoded, Storage::disk(env('FILESYSTEM_DRIVER'))->get($this->video_transcoded));
                        Storage::disk(env('FILESYSTEM_DRIVER'))->delete($this->video_transcoded);
                        $this->video_link = Storage::disk(env('FILESYSTEM_DRIVER_YA'))->url($this->video_transcoded);
                        $this->save();
                    }
                }

                return Storage::disk($this->driver)->url($this->video_transcoded);
            }
            return Storage::disk($this->driver)->url($this->video_transcoded);
        } else {
            if($value) {
                return Storage::disk($this->driver)->url($value);
            }
        }

        return null;
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            if (Storage::disk($this->driver)->exists($value)) {
                return Storage::disk($this->driver)->url($value);
            }

            return url(Storage::disk('local')->url($value));
        }

        return null;
    }

    public function isRus()
    {
        if($this->job && $this->job->agency) {
            return $this->job->agency->isRus();
        }

        return false;
    }
}
