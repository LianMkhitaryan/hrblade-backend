<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'bg_image',
        'header_image',
        'bg_color',
        'buttons_color',
        'description',
    ];

    protected $appends = ['permissions', 'share_hash'];
    protected $hidden = ['hash'];

    public function getLogoAttribute($value)
    {
//        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
//            return $value;
//        }
        if ($value) {
            if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($value)) {
                return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
            }
            return url(Storage::disk('local')->url($value));
        }

        return null;
    }

    public function getShareHashAttribute() {
        $user = Auth::user();

        if($user) {
            if(class_basename($user) == 'Administrator') {
                return $this->hash;
            }
        }

        if ($user) {
            return $this->hash;
        }

        return null;
    }

    public function getBgImageAttribute($value)
    {
        if ($value) {
            if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($value)) {
                return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
            }
            return url(Storage::disk('local')->url($value));
        }

        return null;
    }

    public function getHeaderImageAttribute($value)
    {
        if ($value) {
            if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($value)) {
                return Storage::disk(env('FILESYSTEM_DRIVER'))->url($value);
            }
            return url(Storage::disk('local')->url($value));
        }
        return null;
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function getPermissionsAttribute()
    {
        $user = Auth::user();

        if(class_basename($user) == 'Administrator') {
            return [
                'edit_company' => true,
                'create_jobs' => true,
                'view_rooms' => true,
                'create_rooms' => true,
                'view_jobs' => true
            ];
        }

        if($user) {
            return [
                'edit_company' => $user->perm('edit_company', $this->id),
                'create_jobs' => $user->perm('create_jobs', $this->id),
                'view_jobs' => $user->perm('view_jobs', $this->id),
                'view_rooms' => $user->perm('view_rooms', $this->id),
                'create_rooms' => $user->perm('create_rooms', $this->id)
            ];
        }

        return [
            'edit_company' => false,
            'create_jobs' => false,
            'view_rooms' => false,
            'create_rooms' => false,
            'view_jobs' => false
        ];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
