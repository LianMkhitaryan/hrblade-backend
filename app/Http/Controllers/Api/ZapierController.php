<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Mail\InviteInterwiew;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Hook;
use App\Models\Job;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

class ZapierController extends BaseController
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
            return response()->json(['sessionKey' => $token->plainTextToken]);
        } else {
            return $this->error(__('messages.login_failed'));
        }
    }

    public function me()
    {
        $user = Auth::user();
        return response()->json(['user_id' => $user->id]);
    }

    public function responses(Request $request)
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $companies = $user->agency->companies;
        } else {
            $companies = collect();
            $companiesIds = Permission::where('user_id', $user->id)->get()->pluck('company_id')->toArray();
            if (count($companiesIds)) {
                $companiesIds = array_unique($companiesIds);
                $companies = Company::whereIn('id', $companiesIds)->get();
            }
        }

        if (!$companies->count()) {
            return $this->success([]);
        }

        $jobs = Job::whereIn('company_id', $companies->pluck('id')->toArray())->get();

        if (!$jobs->count()) {
            return $this->success([]);
        }

        $responses = Response::whereIn('job_id', $jobs->pluck('id')->toArray())->get();

        $data = [];

        foreach ($responses as $response) {
            $zapier = [];
            $zapier['id'] = $response->id;
            $zapier['name'] = $response->full;
            $zapier['phone'] = $response->phone;
            $zapier['email'] = $response->email;
            $zapier['location'] = $response->location;
            $zapier['created_at'] = $response->created_at->format('Y-m-d\TH:i\Z');
            $zapier['company_name'] = $response->job->company->name;
            $zapier['job_name'] = $response->job->name;
            $data[] = $zapier;
        }

        return response()->json($data);
    }

    public function subscribe(Request $request)
    {
        $user = Auth::user();

        $hook = new Hook();
        $hook->user_id = $user->id;
        $hook->event_id = $request->event_id;
        $hook->hook_url = $request->hook_url;
        $hook->save();

        Log::info(var_export($request->all(), true));


        if ($user->isOwner()) {
            $companies = $user->agency->companies;
        } else {
            $companies = collect();
            $companiesIds = Permission::where('user_id', $user->id)->get()->pluck('company_id')->toArray();
            if (count($companiesIds)) {
                $companiesIds = array_unique($companiesIds);
                $companies = Company::whereIn('id', $companiesIds)->get();
            }
        }

        if (!$companies->count()) {
            return $this->success([]);
        }

        $jobs = Job::whereIn('company_id', $companies->pluck('id')->toArray())->get();

        if (!$jobs->count()) {
            return $this->success([]);
        }

        $responses = Response::whereIn('job_id', $jobs->pluck('id')->toArray())->get();

        $zapier = [];

        foreach ($responses as $key => $response) {
            $zapier[$key]['id'] = $response->id;
            $zapier[$key]['name'] = $response->full;
            $zapier[$key]['phone'] = $response->phone;
            $zapier[$key]['email'] = $response->email;
            $zapier[$key]['location'] = $response->location;
            $zapier[$key]['created_at'] = $response->created_at;
            $zapier[$key]['company_name'] = $response->job->company->name;
            $zapier[$key]['job_name'] = $response->job->name;
        }

        return response()->json($zapier);
    }

    public function invite(Request $request)
    {
        Log::info(var_export($request->all(), true));

        $user = Auth::user();

        $data = $request->all();

        $job = Job::find($data['interview']);

        if(!$user->agency->companies->count() || !in_array($job->company_id, $user->agency->companies->pluck('id')->toArray())) {
            return response()->json(['body' => __('messages.job_not_found')], 200);
        }

        if (!$job || !$job->active) {
            return response()->json(['body' => __('messages.job_not_found')], 200);
        }

        if ($job->expire_date) {
            if (Carbon::now() > $job->expire_date) {
                return response()->json(['body' => __('messages.job_expired')], 200);
            }
        }

        if ($job->expire_days) {
            if (Carbon::now() > Carbon::parse($job->start_at)->addDays($job->expire_days)) {
                return response()->json(['body' => 'Job expired'], 200);
            }
        }

        $exist = Response::where(function ($query) use ($data) {
            $query->orWhere('email', $data['email'])->orWhere('phone', $data['phone']);
        })->where('job_id', $job->id)->first();

        if($exist) {
            return response()->json(['body' => __('messages.resume_exist')], 200);
        }

        $data['invited'] = 1;
        $data['full'] = $data['name'];
        $data['hash'] = Str::random(26);
        while (Response::where('hash', $data['hash'])->first()) {
            $data['hash'] = Str::random(26);
        }
        $data['job_id'] = $data['interview'];
        $data['company_id'] = $job->company_id;

        unset($data['name']);
        unset($data['interview']);


        $response = Response::create($data);

        $link = new Link();
        $link->job_id = $job->id;
        $link->hash = Str::random(26);
        while (Link::where('hash', $link->hash)->first()) {
            $link->hash = Str::random(26);
        }
        $link->active = 1;
        $link->response_id = $response->id;
        $link->save();

        $language = 'en';

        if ($request->language) {
            if (in_array($request->language, ['ru', 'en', 'es', 'de'])) {
                $language = $request->language;
            }
        }

        $company = $job->company;

        $template = EmailTemplate::where('type', 'INVITE')->where('company_id', $job->company_id)->where('language', $language)->first();

        if(!$template) {
            $template = EmailTemplate::where('type', 'INVITE')->where('default', 1)->where('language', $language)->first();
        }

        App::setLocale($language);

        if(isset($data['phone'])) {
            try {
                SmsHelper::send($template->getSmsContent($response, $company), $data['phone']);
                $smsSended = 1;
            } catch (\Error $error) {
                $smsSended = 0;
            }
        } else {
            $smsSended = 1;
        }

        try {
            Mail::to($response->email)->send(new InviteInterwiew($link,$company, $template));
        } catch (\Exception $e) {
            Log::error("Invite email not send to {$response->email}");
        }

        if( $smsSended) {
            return response()->json(['body' => __('messages.invited')], 200);
        }

        return response()->json(['body' => __('messages.invited_maybe_no_sms')], 200);
    }

    public function jobs(Request $request)
    {
        $user = Auth::user();

        if($user->isOwner()) {
            $companies = $user->agency->companies;
        } else {
            $permissions = Permission::where('user_id', $user->id)->where('name','view_jobs')->get();
            if(!$permissions->count()) {
                return $this->success([]);
            }
            $companiesIds = $permissions->pluck('company_id')->toArray();
            $companiesIds = array_unique($companiesIds);
            $companies = Company::whereIn('id',$companiesIds)->get();
        }

        if (!$companies) {
            return $this->success([]);
        }

        $jobs = Job::whereIn('company_id', $companies->pluck('id'))->orderBy('created_at','desc')->get();

        $zapier = [];

        foreach ($jobs as $key => $job) {
            $zapier[$key]['name'] = $job->name;
            $zapier[$key]['id'] = $job->id;
        }

        return response()->json($zapier);
    }

    public function unsubscribe(Request $request)
    {
        $hook = Hook::where('hook_url', $request->hook_url)->first();

        if ($hook) {
            $hook->delete();
        }
        Log::info(var_export($request->all()));
        return response()->json([]);
    }
}
