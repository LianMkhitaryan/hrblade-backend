<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'salary',
        'company_id',
        'industry_id',
        'role_id',
        'for_follow_up',
        'expire_date',
        'expire_days',
        'start_at',
        'agency_id',
        'header_image',
        'template',
        'ask_cv',
        'block_try',
        'random_order',
        'ask_motivation_letter',
        'video'
    ];

    protected $dates = ['expire_date', 'start_at'];

    protected $casts = [
        'expire_date' => 'datetime:Y-m-d\TH:i:s\Z',
        'start_at' => 'datetime:Y-m-d\TH:i:s\Z',
    ];

    protected $appends = ['hash_link','invited_count', 'interviewed_count','permissions', 'preview_video'];

    protected $responsesIn = false;

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sorting');
    }

    public function pipelines()
    {
        return $this->hasMany(Pipeline::class)->orderBy('sorting');
    }

    public function getHashLinkAttribute()
    {
        $link = Link::where('job_id', $this->id)->where('response_id', null)->first();
        if(!$link) {
            return null;
        }

        return $link->hash;
    }

    public function getPermissionsAttribute()
    {
        $user = Auth::user();

        if($user) {
            if(class_basename($user) == 'Administrator') {
                return [
                    'edit_jobs' => true,
                    'rate_responses' => true
                ];
            }
        }

        if($user) {
            return [
                'edit_jobs' => $user->perm('edit_jobs', $this->company_id),
                'rate_responses' => $user->perm('rate_responses', $this->company_id)
            ];
        }

        return [
            'edit_jobs' => false,
            'rate_responses' => false
        ];
    }

    public function getHeaderImageAttribute($value)
    {
        if($value) {
            if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($value)) {
                return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
            }
            return url(Storage::disk('local')->url($value));
        }

        return null;
    }

    public function getPreviewVideoAttribute()
    {
        $srcPath = "import/intro/intro_" . App::getLocale() . ".mp4";

        if(Storage::disk('public')->exists($srcPath)) {
            return url(Storage::disk('public')->url($srcPath));
        }

        return null;
    }

    public function getInvitedCountAttribute()
    {
        if(!$this->responsesIn) {
            $this->responsesIn = Response::where('job_id', $this->id)->get();
        }

        if($this->responsesIn->count()) {
            return $this->responsesIn->where('invited', 1)->count();
        }

        return 0;
    }

    public function getInterviewedCountAttribute()
    {
        if(!$this->responsesIn) {
            $this->responsesIn = Response::where('job_id', $this->id)->get();
        }

        if($this->responsesIn->count()) {
            return $this->responsesIn->where('status', '!=' , 'INVITED')->count();
        }

        return 0;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }

    public function competences()
    {
        return $this->hasMany(Competence::class);
    }
}
