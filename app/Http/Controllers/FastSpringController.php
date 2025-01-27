<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FastSpringController extends Controller
{
    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::error(print_r($data, 1));
        if (!isset($data['events'])) {
            Log::error('no events');
            return abort(404, 'no events');
        }
        foreach ($data['events'] as $event) {
            if ($event['type'] == 'subscription.activated') {
                $userId = isset($event['data']['tags']['id']) ? $event['data']['tags']['id'] : false;
                if (!$userId) {
                    Log::error('USER_ID' . $userId);
                    return abort(404, 'no email');
                }
                $user = User::find($userId);
                if (!$user) {
                    Log::error($userId);
                    return abort(404, 'no user with id' . $userId);
                }
                $plan_id = isset($event['data']['product']) ? $event['data']['product'] : 0;
                if (!$plan_id) {
                    return abort(404, 'no plan id');
                }
                $plan = Plan::where('plan_uid', $plan_id)->first();
                if (!$plan) {
                    Log::error($plan_id);
                    return abort(404, 'no plan with id' . $plan_id);
                }
                try {
                    $end_date = Carbon::createFromTimestamp($event['data']['nextInSeconds']);
                } catch (\Exception $ex) {
                    Log::error(print_r($event['data'], 1));
                    return abort(404, 'not valid next date');
                }

                $user->agency->plan_id = $plan->id;
                $user->agency->end_subscribe = $end_date;
                $user->fastspring_account = $event['data']['account'];
                $user->agency->subscription_id = $event['data']['id'];
                $user->agency->quantity = $event['data']['quantity'];
                $user->agency->plan_status = 'ACTIVE';
                $user->agency->save();
                $user->save();
                return response()->json(['success' => true], 200);
            } elseif($event['type'] == 'subscription.activated') {
                $userId = isset($event['data']['tags']['id']) ? $event['data']['tags']['id'] : false;
                if (!$userId) {
                    Log::error('USER_ID' . $userId);
                    return abort(404, 'no email');
                }
                $user = User::find($userId);
                if (!$user) {
                    Log::error($userId);
                    return abort(404, 'no user with id' . $userId);
                }
                $plan_id = isset($event['data']['product']) ? $event['data']['product'] : 0;
                if (!$plan_id) {
                    return abort(404, 'no plan id');
                }
                $plan = Plan::where('plan_uid',$plan_id)->first();
                if (!$plan) {
                    Log::error($plan_id);
                    return abort(404, 'no plan with id' . $plan_id);
                }
                try {
                    $end_date = Carbon::createFromTimestamp($event['data']['nextInSeconds']);
                } catch (\Exception $ex) {
                    Log::error(print_r($event['data'], 1));
                    return abort(404, 'not valid next date');
                }

                $user->agency->plan_id = $plan->id;
                $user->agency->end_subscribe = $end_date;
                $user->fastspring_account = $event['data']['account'];
                $user->agency->subscription_id = $event['data']['id'];
                $user->agency->quantity = $event['data']['quantity'];
                $user->agency->plan_status = 'ACTIVE';
                $user->agency->save();
                $user->save();
                return response()->json(['success' => true], 200);
            } elseif ($event['type'] == 'subscription.updated') {
                $userId = isset($event['data']['tags']['id']) ? $event['data']['tags']['id'] : false;
                if (!$userId) {
                    Log::error('USER_ID' . $userId);
                    return abort(404, 'no email');
                }
                $user = User::find($userId);
                if (!$user) {
                    Log::error($userId);
                    return abort(404, 'no user with id' . $userId);
                }
                $plan_id = isset($event['data']['product']) ? $event['data']['product'] : 0;
                if (!$plan_id) {
                    return abort(404, 'no plan id');
                }
                $plan = Plan::where('plan_uid',$plan_id)->first();
                if (!$plan) {
                    Log::error($plan_id);
                    return abort(404, 'no plan with id' . $plan_id);
                }
                try {
                    $end_date = Carbon::createFromTimestamp($event['data']['nextInSeconds']);
                } catch (\Exception $ex) {
                    Log::error(print_r($event['data'], 1));
                    return abort(404, 'not valid next date');
                }

                $user->agency->plan_id = $plan->id;
                $user->agency->end_subscribe = $end_date;
                $user->fastspring_account = $event['data']['account'];
                $user->agency->subscription_id = $event['data']['id'];
                $user->agency->quantity = $event['data']['quantity'];
                $user->agency->save();
                $user->save();
                return response()->json(['success' => true], 200);
            } elseif ($event['type'] == 'subscription.canceled') {
                $account = isset($event['data']['account']) ? $event['data']['account'] : false;
                if (!$account) {
                    return response()->json(['error' => 'no email'], 403);
                }
                $user = User::where('fastspring_account', $account)->first();
                if (!$user) {
                    Log::error($account);
                    return response()->json(['error' => 'no user with fastspring account id' . $account], 403);
                }
                $plan_id = isset($event['data']['product']) ? $event['data']['product'] : 0;
                if (!$plan_id) {
                    return response()->json(['error' => 'no plan id'], 403);
                }
                $plan = Plan::where('plan_uid', $plan_id)->first();
                if (!$plan) {
                    Log::error($plan_id);
                    return response()->json(['error' => 'no plan with id' . $plan_id], 403);
                }

                try {
                    $end_date = Carbon::createFromTimestamp($event['data']['deactivationDateInSeconds']);
                } catch (\Exception $ex) {
                    Log::error(print_r($event['data'], 1));
                    return response()->json(['error' => 'not valid next date'], 403);
                }

                $user->agency->plan_id = $plan->id;
                $user->agency->end_subscribe = $end_date;
                $user->agency->subscription_id = null;
                $user->agency->plan_status = 'CANCELED';
                $user->save();
                $user->agency->save();
                return response()->json(['canceled' => true], 200);
            } elseif ($event['type'] == 'subscription.deactivated') {
                $account = isset($event['data']['account']) ? $event['data']['account'] : false;
                if (!$account) {
                    return abort(404, 'no email');
                }
                $user = User::where('fastspring_account', $account)->first();
                if (!$user) {
                    Log::error($account);
                    return abort(404, 'no user with fastspring account id' . $account);
                }
                $plan_id = isset($event['data']['product']) ? $event['data']['product'] : 0;
                if (!$plan_id) {
                    return abort(404, 'no plan id');
                }
                $plan = Plan::where('plan_uid',$plan_id)->first();
                if (!$plan) {
                    Log::error($plan_id);
                    return abort(404, 'no plan with id' . $plan_id);
                }

                $user->agency->plan_id = $plan->id;
                $user->agency->end_subscribe = Carbon::now();
                $user->agency->subscription_id = null;
                $user->save();
                $user->agency->save();
                return response()->json(['canceled' => true], 200);

            } elseif ($event['type'] == 'subscription.charge.completed') {
                $account = isset($event['data']['account']) ? $event['data']['account'] : false;
                if (!$account) {
                    return abort(404, 'no email');
                }
                $user = User::where('fastspring_account', $account)->first();
                if (!$user) {
                    Log::error($account);
                    return abort(404, 'no user with fastspring account id' . $account);
                }
                $plan_id = isset($event['data']['product']) ? $event['data']['product'] : 0;
                if (!$plan_id) {
                    return abort(404, 'no plan id');
                }
                $plan = Plan::where('plan_uid',$plan_id)->first();
                if (!$plan) {
                    Log::error($plan_id);
                    return abort(404, 'no plan with id' . $plan_id);
                }
                try {
                    $end_date = Carbon::createFromTimestamp($event['data']['nextInSeconds']);
                } catch (\Exception $ex) {
                    Log::error(print_r($event['data'], 1));
                    return abort(404, 'not valid next date');
                }
                $user->agency->plan_id = $plan->id;
                $user->agency->end_subscribe = $end_date;
                $user->agency->save();
                $user->save();
                return response()->json(['canceled' => true], 200);
            }
        }
        return response()->json(['error' => 'not valid event'], 403);
    }
}
