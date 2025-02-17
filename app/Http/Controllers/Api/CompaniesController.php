<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileSaver;
use App\Models\Company;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompaniesController extends BaseController
{
    public function create(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description'=>'sometimes',
            'location' => 'sometimes',
            'website' => 'sometimes',
            'logo' => 'sometimes',
            'industry_id' => 'sometimes|exists:industries,id',
            'bg_image'  => 'sometimes',
            'header_image' => 'sometimes',
            'bg_color' => 'sometimes',
            'buttons_color' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        if(!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        if($user->agency->limits('companies')) {
            return $this->error(__('messages.companies_limit'));
        }

        $company = new Company();
        $company->agency_id = $user->agency_id;
        $company->name = $request->name;

        if ($request->hasFile('logo')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('logo'), 'companies');
            $company->logo = $savedFile;
        }
        if ($request->hasFile('bg_image')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('bg_image'), 'bg_image');
            $company->bg_image = $savedFile;
        }
        if ($request->hasFile('header_image')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('header_image'), 'header_image');
            $company->header_image = $savedFile;
        }

        $company->bg_color = $request->bg_color;
        $company->buttons_color = $request->buttons_color;
        $company->location = $request->location;
        $company->website = $request->website;
        $company->industry_id = $request->industry_id;
        $company->hash = Str::random(26);

        $company->save();

        return $this->success($company, __('messages.company_created'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'name' => 'required|max:255',
            'description' => 'sometimes',
            'location' => 'sometimes',
            'website' => 'sometimes',
            'logo' => 'sometimes',
            'industry_id' => 'sometimes|exists:industries,id',
            'bg_image'  => 'sometimes',
            'header_image' => 'sometimes',
            'bg_color' => 'sometimes',
            'buttons_color' => 'sometimes',

        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $company = Company::find((int)$request->company_id);

        if(!$user->perm('edit_company', $company->id)) {
            return $this->error( __('messages.not_have_permissions'));
        }

        $company->name = $request->name;

        if ($request->hasFile('logo')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('logo'), 'companies');
            $company->logo = $savedFile;
        } elseif ($request->has('logo') && $request->get('logo') === 'clear') {
            $company->logo = '/img/default.png';
        }

        if ($request->hasFile('bg_image')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('bg_image'), 'bg_image');
            $company->bg_image = $savedFile;
        } elseif ($request->has('bg_image') && $request->get('bg_image') === 'clear') {
            $company->bg_image = null;
        }

        if ($request->hasFile('header_image')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('header_image'), 'header_image');
            $company->header_image = $savedFile;
        } elseif ($request->has('header_image') && $request->get('header_image') === 'clear') {
            $company->header_image = null;
        }


        $company->bg_color = $request->bg_color;
        $company->buttons_color = $request->buttons_color;
        $company->location = $request->location;
        $company->website = $request->website;
        $company->industry_id = $request->industry_id;
        $company->save();

        return $this->success($company, __('messages.company_updated'));
    }

    public function companies()
    {
        $user = Auth::user();

        if($user->isOwner()) {
            $companies = $user->agency->companies()->withCount('jobs')->get();
        } else {
            $permissions = Permission::where('user_id', $user->id)->get();
            if(!$permissions->count()) {
                return $this->success([]);
            }
            $companiesIds = $permissions->pluck('company_id')->toArray();
            $companiesIds = array_unique($companiesIds);
            $companies = Company::whereIn('id',$companiesIds)->withCount('jobs')->get();
        }

        return $this->success($companies);
    }

    public function company($id)
    {
        $user = Auth::user();

        $company = Company::with('jobs')->find($id);

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if($user->isOwner() && $user->agency_id == $company->agency_id) {
            return $this->success($company);
        }

        if(Permission::where('user_id', $user->id)->where('company_id', $company->id)->first()) {
            return $this->success($company);
        }

        return $this->error(__('messages.company_not_found'));

    }

    public function remove($id)
    {
        $user = Auth::user();

        $company = Company::with('jobs.responses')->find($id);

        if($user->isOwner() && $user->agency_id == $company->agency_id) {

            foreach ($company->jobs as $job) {
                foreach ($job->responses as $response) {
                    $response->answers()->delete();
                }
                $job->responses()->delete();
                $job->questions()->delete();
            }

            $company->jobs()->delete();
            $company->delete();
            return $this->success(__('messages.company_deleted'));
        }

        return $this->error(__('messages.company_not_found'));
    }

    public function getCompanyByHash($hash)
    {
        $company = Company::where('hash', $hash)->with(['jobs'])->first();

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }


        return $this->success($company);
    }
}
