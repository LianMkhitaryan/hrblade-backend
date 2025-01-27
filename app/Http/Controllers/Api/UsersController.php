<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileSaver;
use App\Mail\InviteRegister;
use App\Models\Agency;
use App\Models\Company;
use App\Models\Invite;
use App\Models\Permission;
use App\Models\PermissionForInvite;
use App\Models\Plan;
use App\Models\PlanStripe;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\InviteCompany;

class UsersController extends BaseController
{
    public function login()
    {
        if (Auth::attempt(
            [
                'email' => request('email'),
                'password' => request('password')
            ]
        )) {
            $user = Auth::user();

            $token = $user->createToken('hrblade');
            return $this->success($token);
        } else {
            return $this->error(__('messages.login_failed'));
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $input = $request->all();

        $input['password'] = Hash::make($input['password']);
        $user = User::create($input);
        $user->role = 'OWNER';
        $user->email_verified_at = Carbon::now();
        $user->save();

        $freePlan = Plan::where('active',1)->orderBy('price')->first();
        $agency = $user->agency()->create(['plan_id' => $freePlan->id]);
        $user->agency()->associate($agency);
        $user->save();

        try {
            $ip = $request->getClientIp();
            $response = Http::acceptJson()->get("http://ip-api.com/json/$ip");

            if ($response->successful()) {
                $body = $response->json();
                if ($body['countryCode']) {
                    $agency->country_code = strtolower($body['countryCode']);
                    $agency->save();
                }
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

        $token = $user->createToken('hrblade');
        return $this->success($token);
    }

    public function registerByLink(Request $request, $hash)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $invite = Invite::where('hash', $hash)->first();

        if(!$invite  && !$invite->used) {
            return $this->error(__('messages.invite_not_found'));
        }

        $input = $request->all();

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
        }

        $invitePermissions = PermissionForInvite::where('invite_id', $invite->id)->get();
        foreach ($invitePermissions as $invitePermission) {
            $permission = new Permission();
            $permission->user_id = $user->id;
            $permission->company_id = $invitePermission->company_id;
            $permission->name = $invitePermission->name;
            $permission->save();
            $invitePermission->delete();
        }


        $token = $user->createToken('hrblade');

        return $this->success($token);
    }

    public function getUser()
    {
        $user = Auth::user();
        $user->agency;

        if(isset($_SERVER["HTTP_CF_IPCOUNTRY"]) && $_SERVER["HTTP_CF_IPCOUNTRY"]) {
            $user->country = $_SERVER["HTTP_CF_IPCOUNTRY"];
        } else {
            $user->country = 'DE';
        }

        return $this->success($user, __('messages.user'));
    }

    public function logout()
    {
        $user = Auth::user();

        $user->currentAccessToken()->delete();

        return $this->success(null, __('messages.logouted'));
    }

    public function settings(Request $request)
    {
        $user = Auth::user();

        if (isset($request->name)) {
            $user->name = trim(strip_tags($request->name));
        }

        if ($request->file('avatar')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('avatar'),'avatar');
            $user->profile_photo_path = $savedFile;
        }

        if (isset($request->email)) {
            $user->email = trim(strip_tags($request->email));
        }

        if (isset($request->phone)) {
            $user->phone = trim(strip_tags($request->phone));
        }

        if ($request->has('recruiting_owner')) {
            if($user->isOwner()) {
                $user->recruiting_owner = $request->get('recruiting_owner');
                if ($request->has('agency_name')) {
                    if($user->agency) {
                        $user->agency->name = $request->get('agency_name');
                        $user->agency->save();
                    }
                }
            }
        }

        $user->save();

        return $this->success($user, __('messages.settings_saved'));
    }

    public function invite(Request $request)
    {
        $user = Auth::user();

        if($user->isOwner() || $user->isAdmin()) {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }

            if(Invite::where('email', $request->email)->first()) {
                return $this->error( __('messages.invite_exist'));
            }

            if(User::where('email', $request->email)->first()) {
                return $this->error(__('messages.user_exist'));
            }

            $invite = new Invite();

            $invite->agency_id = $user->agency_id;
            $invite->email = $request->email;
            $invite->hash = Str::random(26);
            $invite->save();

            try {
                Mail::to($invite->email)->send(new InviteRegister($invite));
            } catch (\Exception $e) {
                Log::error("Invite email not send to invite");
                return $this->error(__('messages.error'));
            }


            return $this->success($invite, __('messages.invite_sent'));
         }

        return $this->error(__('messages.not_have_permissions'));
    }

    public function inviteV2(Request $request)
    {
        $user = Auth::user();

        if($user->isOwner() || $user->isAdmin()) {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }

//            if(Invite::where('email', $request->email)->first()) {
//                return $this->error('Invite already exist');
//            }

            if($user->agency->limits('users')) {
                return $this->error(__('messages.limit_users'));
            }

            $inviteUser = User::where('email', $request->email)->first();

            if($inviteUser && $inviteUser->isOwner()) {
                return $this->error(__('messages.user_owner_other_agency'));
            }

            $invite = new Invite();

            $invite->agency_id = $user->agency_id;
            $invite->email = $request->email;
            $invite->hash = Str::random(26);
            $invite->save();

            $companies = [];

            if(!isset($request->companies) || !is_array($request->companies)) {
                return $this->error(__('messages.select_companies'));
            }

            foreach ($request->companies as $company) {
                $company = json_decode($company);
                $realCompany = Company::where('agency_id', $user->agency_id)->where('id', $company->id)->first();
                $companies[] = $realCompany;
                if(!$realCompany) {
                    return $this->error(__('messages.company_not_found'));
                }

                foreach ($company->permissions as $perm) {
                    if($perm->active){
                        $realPermission = new PermissionForInvite();
                        $realPermission->company_id = $realCompany->id;
                        $realPermission->name = $perm->name;
                        $realPermission->invite_id = $invite->id;
                        $realPermission->save();
                    }
                }
            }


            try {
                if($inviteUser) {
                    Mail::to($invite->email)->send(new InviteCompany($invite, $companies));
                } else {
                    Mail::to($invite->email)->send(new InviteRegister($invite, $companies));
                }
            } catch (\Exception $e) {
                Log::error("Invite email not send to invite");
            }

            return $this->success($invite,  __('messages.invite_sent'));
        }

        return $this->error(__('messages.not_have_permissions'));
    }

    public function inviteCompanyResult(Request $request, $hash)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $invite = Invite::where('hash', $hash)->first();

        if(!$invite  && !$invite->used) {
            return $this->error( __('messages.invite_not_found'));
        }


        $user = User::where('email', $invite->email)->first();

        if(!$user) {
            return $this->error(__('messages.user_not_found'),__('messages.user_not_found'));
        }

        $invite->used = 1;
        $invite->save();

        if($request->status == "ACCEPT") {
            $invitePermissions = PermissionForInvite::where('invite_id', $invite->id)->get();
            foreach ($invitePermissions as $invitePermission) {
                $permission = new Permission();
                $permission->user_id = $user->id;
                $permission->company_id = $invitePermission->company_id;
                $permission->name = $invitePermission->name;
                $permission->save();
                $invitePermission->delete();
            }
        }

        return $this->success('', __('messages.invite_processed'));
    }

    public function all()
    {
        $user = Auth::user();

        $agency = $user->agency;

        if(!$agency) {
            return $this->success([]);
        }

        if(!$user->isOwner()) {
            return $this->success([]);
        }

        $companies = $user->agency->companies;

        if(!$companies->count()) {
            return $this->success([]);
        }

        $permissions = Permission::whereIn('company_id', $companies->pluck('id')->toArray())->get();

        if(!$permissions->count()) {
            return $this->success([]);
        }

        $usersIds = $permissions->pluck('user_id')->toArray();
        $usersIds = array_unique($usersIds);

        $users = User::whereIn('id', $usersIds)->get();

        return $this->success($users);
    }

    public function remove($id)
    {
        $user = Auth::user();

        if(!$user->isOwner()) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $delUser = User::find($id);

        if(!$delUser) {
            return $this->error(__('messages.user_not_found'));
        }

        $companies = $user->agency->companies;

        if(!$companies) {
            return $this->error(__('messages.user_not_found'));
        }

        Permission::whereIn('company_id', $companies->pluck('id')->toArray())->where('user_id', $delUser->id)->delete();

        return $this->success('ok', __('messages.permission_deleted'));
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? $this->success(__('messages.check_email_for_reset_pass'))
            : $this->error(__($status));
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        if(!Hash::check($request->old_password, $user->password)) {
            return $this->error(__('messages.old_password_wrong'));
        }

        $user->password = Hash::make($request->password);

        return  $this->success(__('messages.password_changed'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? $this->success(__('messages.password_changed'))
            : $this->error(__($status));
    }

    public function token()
    {
        $user = Auth::user();

        if(!$user->isOwner()) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $token = $user->createToken('intrewoo');
        $token = $token->plainTextToken;

        return $this->success($token, __('messages.token_created'));
    }
}
