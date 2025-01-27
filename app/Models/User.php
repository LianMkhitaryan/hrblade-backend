<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'email_verified_at',
        'agency_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    protected function profilePhotoDisk()
    {
        return env('FILESYSTEM_DRIVER');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function isOwner()
    {
        if($this->role == 'OWNER') {
            return true;
        }

        return false;
    }

    public function perm($name, $companyId) {
        $user = Auth::user();
        if($user) {
            if($user->isOwner()) {
                $company = Company::find($companyId);
                if(!$company) {
                    return false;
                }
                if($company->agency_id == $user->agency_id) {
                    return true;
                }
            }
            return Permission::where('company_id', $companyId)->where('user_id', $user->id)->where('name', $name)->exists();
        }

        return false;
    }

    public function isManager()
    {
        if($this->role == 'OWNER' || $this->role == 'MANAGER') {
            return true;
        }

        return false;
    }

    protected function defaultProfilePhotoUrl()
    {
        return url('/img/default.jpg');
    }

    public function getProfilePhotoUrlAttribute()
    {
        if($this->getOriginal('profile_photo_path')) {
            if(Storage::disk($this->profilePhotoDisk())->exists($this->getOriginal('profile_photo_path'))) {
                return Storage::disk($this->profilePhotoDisk())->url($this->getOriginal('profile_photo_path'));
            }
        }

        return $this->defaultProfilePhotoUrl();
    }

    public function getQuantityUsers()
    {
        $user = Auth::user();
        if($user) {
            if($user->isOwner()) {
                $agency = $user->agency;
                if(!$agency) {
                    return false;
                }
                $companies = $agency->companies;

                if($companies->count()) {
                    $permissions = Permission::whereIn('company_id', $companies->pluck('id')->toArray())->get();
                    if($permissions->count()) {
                        $permissionsUsersIds = $permissions->pluck('user_id')->toArray();
                        $permissionsUsersIds = array_unique($permissionsUsersIds);
                        return count($permissionsUsersIds);
                    }
                    return false;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    public function createToken(string $name, array $abilities = ['*'])
    {
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(64)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $plainTextToken);
    }
}
