<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Response extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'full',
        'email',
        'phone',
        'job_id',
        'status',
        'hash',
        'note',
        'company_id',
        'language',
        'pipeline_id',
        'agency_id',
        'invited'
    ];

    protected $hidden = ['hash'];

    protected $dates = ['completed_at', 'visited_at'];

    protected $casts = [
        'completed_at' => 'datetime:Y-m-d\TH:i:s\Z',
        'visited_at' => 'datetime:Y-m-d\TH:i:s\Z',
    ];

    protected $appends = ['share_hash', 'permissions'];

    protected static function booted()
    {
        static::deleted(function ($response) {
            if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists($response->default_cv)){
                Storage::disk(env('FILESYSTEM_DRIVER'))->delete($response->default_cv);
            }
            if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists($response->ask_motivation_letter)){
                Storage::disk(env('FILESYSTEM_DRIVER'))->delete($response->ask_motivation_letter);
            }
            if(Storage::disk(env('FILESYSTEM_DRIVER'))->exists($response->ask_cv)){
                Storage::disk(env('FILESYSTEM_DRIVER'))->delete($response->ask_cv);
            }
        });
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function link()
    {
        return $this->hasOne(Link::class);
    }

    public function getShareHashAttribute()
    {
        $user = Auth::user();

        if($user) {
            if(class_basename($user) == 'Administrator') {
                return $this->hash;
            }
        }

        if ($user) {
            if($user->perm('view_jobs', $this->company_id)) {
                    return $this->hash;
            }
        }

        return null;
    }

    public function getPermissionsAttribute()
    {
        $user = Auth::user();

        if($user) {
            if(class_basename($user) == 'Administrator') {
                return [
                    'rate_responses' => true
                ];
            }
        }


        if ($user) {
            return [
                'rate_responses' => $user->perm('rate_responses', $this->company_id)
            ];
        }

        return [
            'rate_responses' => false
        ];
    }

    public function getAskCvAttribute($value)
    {
        if($value) {
            return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
        }

        return null;
    }

    public function getDefaultCvAttribute($value)
    {
        if($value) {
            return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
        }

        return null;
    }

    public function getAskMotivationLetterAttribute($value)
    {
        if($value) {
            return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
        }

        return null;
    }
}
