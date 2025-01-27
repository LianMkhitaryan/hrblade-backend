<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Room extends Model
{
    use HasFactory;

    protected $hidden = ['hash'];

    protected $appends = ['share_hash', 'permissions'];

    protected $dates = ['start'];

    protected $fillable = ['name', 'description', 'start','hash', 'source', 'link', 'room_id', 'company_id'];

    public function getShareHashAttribute()
    {
        $user = Auth::user();

        if ($user && $user->perm('view_rooms', $this->company_id)) {
            return $this->hash;
        }

        return null;
    }

    public function getPermissionsAttribute()
    {
        $user = Auth::user();
        if ($user) {
            return [
                'edit_rooms' => $user->perm('edit_rooms', $this->company_id)
            ];
        }

        return [
            'edit_rooms' => false
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
