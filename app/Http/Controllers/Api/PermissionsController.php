<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invite;
use App\Models\Permission;
use App\Models\PermissionForInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionsController extends BaseController
{
    public function getInvite($hash)
    {
        $invite = Invite::where('hash', $hash)->first();

        if(!$invite  || $invite->used) {
            return $this->error(__('messages.invite_not_found'));
        }

        $permissions = PermissionForInvite::where('invite_id', $invite->id)->get();

        $companies = [];

        if($permissions->count()) {
            $companiesIds = $permissions->pluck('company_id')->toArray();
            $companiesIds = array_unique($companiesIds);
            $companies = Company::whereIn('id', $companiesIds)->select(['id','name'])->get();
        }

        return $this->success(['companies' => $companies]);
    }

    public function get(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $permUser = User::find($request->user_id);

        if (!$permUser) {
            return $this->error(__('messages.user_not_found'));
        }
        if (!$user->agency || !$user->agency->companies->count()) {
            return $this->error(__('messages.user_not_found'));
        }

        $companies = $user->agency->companies()->withCount('jobs')->get();

        $allUserPermissions = Permission::where('user_id', $permUser->id)->whereIn('company_id', $companies->pluck('id')->toArray())->get();

        return $this->success($allUserPermissions, 'ok');
    }

    public function set(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $permUser = User::find($request->user_id);

        if (!$permUser) {
            return $this->error(__('messages.user_not_found'));
        }
        if (!$user->agency || !$user->agency->companies->count()) {
            return $this->error(__('messages.user_not_found'));
        }

        $companies = $user->agency->companies;

        if (!Permission::where('user_id', $permUser->id)->whereIn('company_id', $companies->pluck('id')->toArray())->first()) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $allUserPermissions = Permission::where('user_id', $permUser->id)->whereIn('company_id', $companies->pluck('id')->toArray())->get();



        if(isset($request->companies) && is_array($request->companies)) {
            $allCompanies = $user->agency->companies;
            if($allCompanies->count()) {
                $allRemoveCompanies = $allCompanies->pluck('id')->toArray();
            }

            foreach ($request->companies as $company) {
                $company = json_decode($company);
                $realCompany = $companies->where('id', $company->id)->first();
                if (!$realCompany) {
                    return $this->error(__('messages.company_not_found'));
                }
                if (($key = array_search($realCompany->id, $allRemoveCompanies)) !== false) {
                    unset($allRemoveCompanies[$key]);
                }
                $activePermissions = [];
                foreach ($company->permissions as $perm) {
                    if ($perm->active) {
                        $exist = $allUserPermissions->where('company_id', $realCompany->id)->where('name', $perm)->first();
                        if (!$exist) {
                            $permission = new Permission();
                            $permission->user_id = $permUser->id;
                            $permission->company_id = $realCompany->id;
                            $permission->name = $perm->name;
                            $permission->save();
                            $activePermissions[] = $permission->id;
                        } else {
                            $activePermissions[] = $exist->id;
                        }
                    }
                }
                Permission::where('user_id', $permUser->id)->where('company_id', $realCompany->id)->whereNotIn('id', $activePermissions)->delete();
            }
        } else {
            $allCompanies = $user->agency->companies;
            if($allCompanies->count()) {
                $allRemoveCompanies = $allCompanies->pluck('id')->toArray();
            } else {
                $allRemoveCompanies = [];
            }
        }

        if(count($allRemoveCompanies)) {
            Permission::where('user_id', $permUser->id)->whereIn('company_id', $allRemoveCompanies)->delete();
        }



        $allUserPermissions = Permission::where('user_id', $permUser->id)->whereIn('company_id', $companies->pluck('id')->toArray())->get();

        return $this->success($allUserPermissions, __('messages.permissions_saved'));
    }
}
