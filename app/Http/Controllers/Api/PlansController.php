<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PlanBuy;
use App\Models\Job;
use App\Models\Plan;
use App\Models\Response;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PlansController extends BaseController
{
    public function all()
    {
        return $this->success(Plan::where('active', 1)->get());
    }

    public function current()
    {
        $user = Auth::user();
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

        return $this->success($plan);
    }

    public function sendEmail(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'plan' => 'required',
            'currency' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            Mail::to(env('PLAN_EMAIL'))->send(new PlanBuy($user, $request));
        } catch (\Exception $e) {
            Log::error("Plan email email not send to user ID {$user->id}");
        }
        
        return $this->success('', __('messages.email_sent'));
    }

    public function cancelSubscription()
    {
        $user = Auth::user();

        if(!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        if (!$user->agency->subscription_id) {
            return $this->error(__('messages.subscription_not_found'));
        }

        $response = $this->apiRequest('GET', implode(
            '/',
            ['subscriptions', implode(',', [$user->agency->subscription_id])]
        ), [], [], []);

        if (!$response) {
            return $this->error(__('messages.subscription_not_found'));
        }

        $response = $this->apiRequest('DELETE', implode('/', ['subscriptions', $user->agency->subscription_id]).'?billingPeriod=0', [], [], []);

        if ($response) {
            return $this->success(__('messages.plan_canceled'));
        }

        return $this->error(__('messages.error'));
    }

    public function changeQuantity(Request $request)
    {
        $user = Auth::user();

        if(!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        if (!$user->agency->subscription_id) {
            return $this->error(__('messages.subscription_not_found'));
        }

        $response = $this->apiRequest('GET', implode(
            '/',
            ['subscriptions', implode(',', [$user->agency->subscription_id])]
        ), [], [], []);

        if (!$response) {
            return $this->error(__('messages.subscription_not_found'));
        }

        $quantity = $request->quantity;

        $userQuantity = $user->getQuantityUsers();

        if($userQuantity && $userQuantity > $quantity) {
            return $this->error('The number of existing users exceeds the limit');
        }

        $response = $this->updateSubscriptions([
            [
                'subscription' => $user->agency->subscription_id,
                'quantity' => $quantity
            ],
        ]);

        if ($response) {
            return $this->success('Plan updated');
        }

        return $this->error('Error');
    }

    public function apiRequest($method, $path, $query = [], $formParameters = [], $jsonPayload = [])
    {

        $clientOptions = ['base_uri' => 'https://api.fastspring.com'];
        $client = new Client($clientOptions);

        $path = ltrim($path, '/');

        $options = [
            'auth' => [env('FASTSPRING_ACC'), env('FASTSPRING_PASSWORD')],
            'query' => [],
        ];

        $options['query'] = array_merge($options['query'], $query);

        if ($formParameters) {
            $options['form_params'] = $formParameters;
        }

        if ($jsonPayload) {
            $options['json'] = $jsonPayload;
        }

        $response = $client->request($method, $path, $options);

        $message = $response->getBody()->getContents();

        return json_decode($message);

        try {
            $response = $client->request($method, $path, $options);

            $message = $response->getBody()->getContents();

            return json_decode($message);
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function updateSubscriptions($subscriptions)
    {
        return $this->apiRequest('POST', 'subscriptions', [], [], [
            'subscriptions' => $subscriptions,
        ]);
    }
}
