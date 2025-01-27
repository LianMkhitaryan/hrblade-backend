<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as FFMpeg;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['question_id','answer','video','response_id'];

    protected $hidden = ['video_transcoded'];

    public $driver;


    public function question()
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    public function response()
    {
        return $this->belongsTo(Response::class);
    }

    public function copyscapes()
    {
        return $this->hasMany(CopyscapeUrl::class);
    }

    protected static function booted()
    {
        static::deleted(function ($answer) {
            if(Storage::disk($answer->driver)->exists($answer->video)){
                Storage::disk($answer->driver)->delete($answer->video);
            }
            if(Storage::disk($answer->driver)->exists($answer->video_thumb)){
                Storage::disk($answer->driver)->delete($answer->video_thumb);
            }
            if(Storage::disk($answer->driver)->exists($answer->video_gif)){
                Storage::disk($answer->driver)->delete($answer->video_gif);
            }
            if(Storage::disk($answer->driver)->exists($answer->video_transcoded)){
                Storage::disk($answer->driver)->delete($answer->video_transcoded);
            }
        });

        static::retrieved(function ($answer) {
            $answer->driver = $answer->isRus() ? env('FILESYSTEM_DRIVER_YA') : env('FILESYSTEM_DRIVER');
        });
    }

    public function getVideoAttribute($value)
    {
        //Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($this->video_transcoded, 'private');
        if($this->video_transcoded) {
            if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists($this->video_transcoded)) {
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->getVisibility($this->video_transcoded) == 'private') {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($this->video_transcoded, 'public');
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($value);
                    try {
                        $time = FFMpeg::fromDisk(env('FILESYSTEM_DRIVER'))->open($this->video_transcoded)->getDurationInSeconds();
                        $data = Carbon::parse('17.11.1984 00:00')->addSeconds($time);
                        $this->video_time = $data->format('i:s');
                        $this->save();
                    } catch (\Error $e) {
                        Log::error("VIDEO DURATION ERROR ANSWER_ID=$this->id");
                    }

                    if($this->isRus()) {
                        Storage::disk(env('FILESYSTEM_DRIVER_YA'))->put($this->video_transcoded, Storage::disk(env('FILESYSTEM_DRIVER'))->get($this->video_transcoded));
                        Storage::disk(env('FILESYSTEM_DRIVER'))->delete($this->video_transcoded);
                    }
                }

                return Storage::disk($this->driver)->url($this->video_transcoded);
            }
            return Storage::disk($this->driver)->url($this->video_transcoded);
        } else {
            if($value) {
                return Storage::disk($this->driver)->url($this->video_transcoded);
            }
        }

        return null;
    }

    public function getVideoThumbAttribute($value)
    {
        if($value) {
            return Storage::disk($this->driver)->url($value);
        }

        return null;
    }

    public function getVideoGifAttribute($value)
    {
        if($value) {
            return Storage::disk($this->driver)->url($value);
        }

        return null;
    }

    public function isRus()
    {
        if($this->response && $this->response->agency) {
            return $this->response->agency->isRus();
        }

        return false;
    }
}
