<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;
    use Billable;

    protected $fillable = ['plan_id'];

    public function taxRates()
    {

        if ($this->hasStripeId()) {
            $customer = $this->asStripeCustomer();
            if ($this->client_type == 'business') {
                if($customer->address) {
                    $ourTax = Tax::where('active', 1)->where('country', $customer->address->country)->first();
                    if($ourTax) {
                        if($ourTax->country == 'EE') {
                            return [$ourTax->stripe_id];
                        }
                        if (isset($customer->tax_ids['data']) && is_array($customer->tax_ids['data']) && count($customer->tax_ids['data'])) {
                            foreach ($customer->tax_ids['data'] as $tax) {
                                if ($tax->country == $ourTax->country) {
                                    return [];
                                }
                            }
                        }
                        return [$ourTax->stripe_id];
                    }
                }
            } else {
                if($customer->address) {
                    $ourTax = Tax::where('active', 1)->where('country', $customer->address->country)->first();
                    if($ourTax) {
                        if($ourTax->country == 'EE') {
                            return [$ourTax->stripe_id];
                        }
                        return [$ourTax->stripe_id];
                    }
                }
            }
        }

        return [];
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->hasOne(User::class)->where('role', 'OWNER');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPremium()
    {
        if ($this->plan_id != 1) {
            return true;
        }

        return false;
    }

    public function isEnterprise()
    {
        $agency = $this;
        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();
        $stripePlan = $agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();
        if ($stripePlan) {
            if($stripePlan->name == "HRBLADE Enterprise") {
                return true;
            }

        }

        return false;
    }

    public function limits($type)
    {
        $agency = $this;
        if (!$agency) {
            return true;
        }
        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();
        $stripePlan = $agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();
        if ($stripePlan) {
            $plan = PlanStripe::where('stripe_name', $stripePlan->name)->first();
            $start = Carbon::now()->subDays(30);
            $end = Carbon::now();
        } else {
            $plan = PlanStripe::where('price', 0)->first();
            $start = Carbon::now()->subDays(30);
            $end = Carbon::now();
        }

        if (!$plan) {
            return true;
        }

        switch ($type) {
            case 'users':
                $companies = Company::where('agency_id', $agency->id)->get();

                if (!$companies->count()) {
                    return false;
                }

                $permissions = Permission::whereIn('company_id', $companies->pluck('id')->toArray())->get();

                if (!$permissions->count()) {
                    return false;
                }

                $usersIds = $permissions->pluck('user_id')->toArray();
                $usersIds = array_unique($usersIds);

                $users = User::whereIn('id', $usersIds)->get();

                $usersLimit = $agency->users_limit > 0 ? $agency->users_limit : $plan->users_limit;

                if ($users->count() < $usersLimit) {
                    return false;
                }
                break;
            case 'jobs':
                $jobs = Job::where('agency_id', $agency->id)->get();
                $interviewsLimit = $agency->interviews_limit > 0 ? $agency->interviews_limit : $plan->interviews_limit;
                if ($jobs->count() < $interviewsLimit) {
                    return false;
                }
                break;
            case 'questions':
                return $plan->questions_limit;
            case 'copyscape':
                $copyscapesCount = CopyscapeUrl::where('agency_id', $agency->id)
                    ->where('created_at', '>', $start)
                    ->where('created_at', '<', $end)
                    ->groupBy('answer_id')
                    ->get()
                    ->count();

                if ($copyscapesCount < $plan->copyscape_limit) {
                    return false;
                }
                break;
            case 'companies':
                $companies = Company::where('agency_id', $agency->id)->get();

                $companiesLimit = $agency->companies_limit > 0 ? $agency->companies_limit : $plan->companies_limit;

                if ($companies->count() < $companiesLimit) {
                    return false;
                }
                break;
            case 'responses':
                $responses = Response::withTrashed()
                    ->where('agency_id', $agency->id)
                    ->where('created_at', '>', $start)
                    ->where('created_at', '<', $end)
                    ->where('status', '!=', 'NEW')
                    ->where('status', '!=', 'INVITED')
                    ->count();

                $responsesLimit = $agency->responses_limit > 0 ? $agency->responses_limit : $plan->responses_limit;

                if ($responses < $responsesLimit) {
                    return false;
                }
                break;
        }

        return true;
    }

    public function isRus() {
        return $this->country_code == 'ru';
    }
}
