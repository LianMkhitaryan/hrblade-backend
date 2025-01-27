<?php

namespace App\Actions\Fortify;

use App\Models\Agency;
use App\Models\Invite;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
        ])->validate();

        if(isset($input['hash'])) {
            $invite = Invite::where('hash', $input['hash'])->first();
            if($invite && !$invite->used) {
                $agency = Agency::find($invite->agency_id);

                if($agency) {
                    $user = User::create([
                        'name' => $input['name'],
                        'email' => $invite->email,
                        'role' => 'MANAGER',
                        'email_verified_at' => Carbon::now(),
                        'password' => Hash::make($input['password']),
                    ]);

                    $user->agency()->associate($agency);
                    $user->save();

                    $invite->used = 1;
                    $invite->save();

                    return $user;
                }
            }
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => 'OWNER',
            'password' => Hash::make($input['password']),
        ]);

        $freePlan = Plan::where('active',1)->orderBy('price')->first();

        $agency = $user->agency()->create(['plan_id' => $freePlan->id]);

        $user->agency()->associate($agency);

        $user->save();

        $user->sendEmailVerificationNotification();


        return $user;
    }
}
