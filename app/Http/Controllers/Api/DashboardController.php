<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Response;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends BaseController
{
    public function get()
    {
        $user = Auth::user();

        $agency = $user->agency;

        $data['companies'] = $agency->companies()->withCount('jobs')->take(3)->orderBy('id', 'desc')->get();

        $companies = $agency->companies;

        if (!$companies) {
            $data['jobs'] = [];
            $data['responses'] = [];
        } else {
            $allJobs = Job::whereIn('company_id', $companies->pluck('id'))->get();
            if ($allJobs->count()) {
                $data['responses'] = Response::whereIn('job_id', $allJobs->pluck('id'))
                    ->where('created_at', '>', Carbon::now()->subDays(30))
                    ->select(['id', 'status', 'created_at', 'invited'])
                    ->get();
            }

            $data['jobs'] = Job::with('company')->whereIn('company_id', $companies->pluck('id'))->take(3)->orderBy('id', 'desc')->get();
        }

        $data['users'] = $agency->users()->take(3)->orderBy('id', 'desc')->get();


        $stripePlan = $user->agency->subscriptions()->active()->first();
        if ($stripePlan) {
            $plan = Plan::where('stripe_name', $user->agency->subscriptions()->first()->name)->first();
            $start = $stripePlan->created_at;
            $end = Carbon::parse($stripePlan->created_at)->addDays(30);
        } else {
            $plan = Plan::where('id', env('FREE_ID'))->first();
            $start = Carbon::now()->subDays(30);
            $end = Carbon::now();
        }

        $plan->start_at = $start->format('Y-m-d\TH:i:s\Z');
        $plan->end_at = $end->format('Y-m-d\TH:i:s\Z');

        $jobs = Job::where('agency_id', $user->agency_id)->get();
        $plan->responses_count = 0;
        if ($jobs->count()) {
            $plan->responses_count = Response::whereIn('job_id', $jobs->pluck('id')->toArray())->where('status', '!=', 'INVITED')->count();
        }

        $next = Plan::where('price', '>', $plan->price)->orderBy('price', 'asc')->first();
        if ($next) {
            $plan->next_price = $next->price;
            $plan->next_link = url(route('plan.get', $next->id));
        } else {
            $plan->next_price = null;
            $plan->next_link = null;
        }

        $data['plan'] = $plan;

        return $this->success($data);
    }

    public function start()
    {
        $user = Auth::user();


        $data['config']['roles'] = Role::select(['id', 'name', 'industry_id'])->where('active', 1)->get();
        $data['config']['industries'] = Industry::select(['id', 'name'])->where('active', 1)->get();
        $data['config']['subjects'] = [
            'Help',
            'Plan'
        ];
        $data['config']['video_url'] = 'https://reallang.chat/';
        $data['config']['permissions'] = [
            ['edit_company' => 'Edit Company'],
            ['view_jobs' => 'View Jobs'],
            ['create_jobs' => 'Create Jobs'],
            ['edit_jobs' => 'Edit Jobs'],
            ['rate_responses' => 'Rate Candidates'],
            ['view_rooms' => 'View rooms'],
            ['create_rooms' => 'Create rooms'],
            ['edit_rooms' => 'Edit rooms'],
        ];

        $data['plans'] = Plan::where('active', 1)->get();

        $plan = $user->agency->plan;

        if ($plan && $user->agency->end_subscribe && $user->agency->end_subscribe > Carbon::now() && $plan->price > 0) {
            $start = Carbon::parse($user->agency->end_subscribe)->subDays(30);
            $end = Carbon::parse($user->agency->end_subscribe);
        } else {
            $plan = Plan::where('id', env('FREE_ID'))->first();
            $start = Carbon::now()->subDays(30);
            $end = Carbon::now();
        }

        $plan->start_at = $start->format('Y-m-d\TH:i:s\Z');
        $plan->end_at = $end->format('Y-m-d\TH:i:s\Z');

        $jobs = Job::where('agency_id', $user->agency_id)->get();
        $plan->responses_count = 0;
        if ($jobs->count()) {
            $plan->responses_count = Response::whereIn('job_id', $jobs->pluck('id')->toArray())->where('status', '!=', 'INVITED')->count();
        }

        $next = Plan::where('price', '>', $plan->price)->orderBy('price', 'asc')->first();
        if ($next) {
            $plan->next_price = $next->price;
            $plan->next_link = null;
        } else {
            $plan->next_price = null;
            $plan->next_link = null;
        }

        $data['current_plan'] = $plan;

        $user->agency;
        $data['user'] = $user;

        if ($user->isOwner()) {
            $companies = $user->agency->companies()->withCount('jobs')->get();
        } else {
            $permissions = Permission::where('user_id', $user->id)->get();
            if (!$permissions->count()) {
                return $this->success([]);
            }
            $companiesIds = $permissions->pluck('company_id')->toArray();
            $companiesIds = array_unique($companiesIds);
            $companies = Company::whereIn('id', $companiesIds)->withCount('jobs')->get();
        }

        $data['companies'] = $companies;

        if (!$companies) {
            $data['jobs'] = [];
        } else {
            $jobs = Job::whereIn('company_id', $companies->pluck('id'))->orderBy('created_at', 'desc')->get();
            $data['jobs'] = $jobs;
        }

        $agency = $user->agency;

        if(!$agency) {
            $data['users'] = [];
        }

        if(!$user->isOwner()) {
            $data['users'] = [];
        }

        $companies = $user->agency->companies;

        if(!$companies->count()) {
            $data['users'] = [];
        }

        $permissions = Permission::whereIn('company_id', $companies->pluck('id')->toArray())->get();

        if(!$permissions->count()) {
            $data['users'] = [];
        }

        $usersIds = $permissions->pluck('user_id')->toArray();
        $usersIds = array_unique($usersIds);

        $users = User::whereIn('id', $usersIds)->get();

        $data['users'] = $users;

        return $this->success($data);
    }
}
