<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Plan;
use App\Models\PlanStripe;
use App\Models\Promocode;
use App\Models\Response;
use App\Models\Tax;
use App\Models\User;
use Carbon\Carbon;
use Ibericode\Vat\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;
use Stripe\Customer;

class StripeController extends BaseController
{
    public function plans()
    {
        $user = Auth::user();

        $plans = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

        $stripePlan = $user->agency->subscriptions()->active()->whereIn('name', $plans->pluck('stripe_name')->toArray())->first();

        if ($stripePlan) {
            foreach ($plans as $plan) {
                foreach ($plan->prices as $price) {
                    if ($stripePlan->stripe_plan == $price['stripe_price_id']) {
                        $needCurrency = $price['currency'];
                    }
                }
            }
        }

        if (isset($needCurrency)) {
            foreach ($plans as $plan) {
                $newPrices = [];
                foreach ($plan->prices as $price) {
                    if ($price['currency'] == $needCurrency) {
                        $newPrices[] = $price;
                    }
                }
                $plan->prices = $newPrices;
            }
        }

        $data['plans'] = $plans;

        return $this->success($data);
    }

    public function createIntent(Request $request)
    {
        $user = Auth::user();
        $user->agency->stripe_id = null;
        $user->agency->card_brand = null;
        $user->agency->card_last_four = null;
        $user->agency->trial_ends_at = null;
        $user->agency->save();

        if (!$request->currency || !in_array($request->currency, ['EUR', 'USD', 'GBP'])) {
            return $this->error('need valid currency');
        }

        config(['cashier.currency' => $request->currency]);

        $intent = $user->agency->createSetupIntent();
        $data['intent'] = $intent;
        $data['api_key'] = env('STRIPE_KEY');

        return $this->success($data);
    }

    public function current()
    {
        $user = Auth::user();
        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();
        $stripePlan = $user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();
        if ($stripePlan) {
            $plan = PlanStripe::where('stripe_name', $stripePlan->name)->first();
            $start = Carbon::now()->subDays(30);
            $end = Carbon::now();
            if ($stripePlan->stripe_id == 'manually') {
                $plan->trial = 1;
            } else {
                $plan->trial = 0;
            }
            $plan->end_plan_at = $stripePlan->ends_at;
        } else {
            $plan = PlanStripe::where('price', 0)->first();
            $plan->trial = 0;
            $start = Carbon::now()->subDays(30);
            $end = Carbon::now();
            $plan->end_plan_at = null;
        }



        if ($user->agency->hasStripeId()) {
            $customer = $user->agency->asStripeCustomer();
            if($customer) {
                $billingInfo = [];
                $billingInfo['client_type'] = $user->agency->client_type;
                $billingInfo['name'] = $customer->name;
                $billingInfo['email'] = $customer->email;
                $billingInfo['city'] = $customer->address->city;
                $billingInfo['state'] = $customer->address->state;
                $billingInfo['postal_code'] = $customer->address->postal_code;
                $billingInfo['country'] = $customer->address->country;
                $billingInfo['company_name'] = $customer->address->line1;
                $billingInfo['address'] = $customer->metadata->address;
                $billingInfo['real_name'] = $customer->metadata->real_name;
                $billingInfo['vat'] = null;

                if (isset($customer->tax_ids['data']) && is_array($customer->tax_ids['data']) && count($customer->tax_ids['data'])) {
                    foreach ($customer->tax_ids['data'] as $tax) {
                        $billingInfo['vat'] = $tax->value;
                        break;
                    }
                }

                $plan->billing_info = $billingInfo;
            } else {
                $billingInfo = [];
                $billingInfo['client_type'] = $user->agency->client_type;
                $billingInfo['name'] = null;
                $billingInfo['email'] = null;
                $billingInfo['city'] = null;
                $billingInfo['state'] = null;
                $billingInfo['postal_code'] = null;
                $billingInfo['country'] = null;
                $billingInfo['company_name'] = null;
                $billingInfo['address'] = null;
                $billingInfo['real_name'] = null;
                $billingInfo['vat'] = null;

                $plan->billing_info = $billingInfo;
            }

        } else {
            $billingInfo = [];
            $billingInfo['client_type'] = $user->agency->client_type;
            $billingInfo['name'] = null;
            $billingInfo['email'] = null;
            $billingInfo['city'] = null;
            $billingInfo['state'] = null;
            $billingInfo['postal_code'] = null;
            $billingInfo['country'] = null;
            $billingInfo['company_name'] = null;
            $billingInfo['address'] = null;
            $billingInfo['real_name'] = null;
            $billingInfo['vat'] = null;

            $plan->billing_info = $billingInfo;
        }

        $plan->start_at = $start->format('Y-m-d\TH:i:s\Z');
        $plan->end_at = $end->format('Y-m-d\TH:i:s\Z');

        $plan->responses_count = Response::withTrashed()
            ->where('agency_id', $user->agency_id)
            ->where('created_at', '>', $start)
            ->where('created_at', '<', $end)
            ->where('status', '!=', 'NEW')
            ->where('status', '!=', 'INVITED')
            ->count();

        $plan->responses_deleted_count = Response::onlyTrashed()
            ->where('agency_id', $user->agency_id)
            ->where('created_at', '>', $start)
            ->where('created_at', '<', $end)
            ->where('status', '!=', 'NEW')
            ->where('status', '!=', 'INVITED')
            ->count();

        if($user->agency) {
            if($user->agency->interviews_limit > 0) {
                $plan->interviews_limit = $user->agency->interviews_limit;
            }
            if($user->agency->users_limit > 0) {
                $plan->users_limit = $user->agency->users_limit;
            }
            if($user->agency->companies_limit > 0) {
                $plan->companies_limit = $user->agency->companies_limit;
            }
            if($user->agency->responses_limit > 0) {
                $plan->responses_limit = $user->agency->responses_limit;
            }
        }

        $next = PlanStripe::where('price', '>', $plan->price)->orderBy('price', 'asc')->first();
        if ($next) {
            $plan->next_price = $next->price;
            $plan->next = $next;
        } else {
            $plan->next_price = null;
            $plan->next = null;
        }

        $plan->can_copyscape = 0;

        if($user->agency->isEnterprise()) {
            $plan->can_copyscape = 1;
        }

        return $this->success($plan);
    }

    public function subscribe(Request $request)
    {
        $plan = PlanStripe::where('id', $request->get('plan_id'))->where('active', 1)->where('price', '>', 0)->where('extra', 0)->first();

        if (!$plan) {
            return $this->error(__('messages.plan_not_found'));
        }

        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

        if ($user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first()) {
            return $this->error(__('messages.active_plan_exist'));
        }


        if (!in_array($request->price_id, collect($plan->prices)->pluck('stripe_price_id')->toArray())) {
            return $this->error(__('messages.price_not_found'));
        }


        $planPrice = false;


        foreach ($plan->prices as $price) {
            if ($price['stripe_price_id'] == $request->price_id) {
                $planPrice = $price;
                break;
            }
        }


        config(['cashier.currency' => $price['currency']]);

        if ($request->has('name') && $request->has('email')) {
            $user->agency->createOrGetStripeCustomer();
            if ($request->has('client_type')) {
                if ($request->get('client_type') == 'business') {
                    $options = [];
                    $options['name'] = $request->name;
                    $options['email'] = $request->email;
                    $options['address']['city'] = $request->city;
                    $options['address']['state'] = $request->state;
                    $options['address']['postal_code'] = $request->postal_code;
                    $options['address']['country'] = $request->country;
                    $options['address']['line1'] = $request->name;
                    $options['metadata']['address'] = $request->address;
                    $options['metadata']['real_name'] = $request->real_name;
                    $options['tax_exempt'] = 'none';
                    $user->agency->updateStripeCustomer($options);
                    $user->agency->client_type = 'business';
                    $user->agency->save();

                    if ($request->has('vat')) {
                        $vatNumber = preg_replace('/[^a-zA-Z0-9]/', '', $request->vat);
                        $validator = new Validator();
                        try {
                            $res = $validator->validateVatNumber($vatNumber);
                        } catch (\Exception $e) {
                            $faults = array(
                                'INVALID_INPUT' => 'The provided CountryCode is invalid or the VAT number is empty',
                                'SERVICE_UNAVAILABLE' => 'The SOAP service is unavailable, try again later',
                                'MS_UNAVAILABLE' => 'The Member State service is unavailable, try again later or with another Member State',
                                'TIMEOUT' => 'The Member State service could not be reached in time, try again later or with another Member State',
                                'SERVER_BUSY' => 'The service cannot process your request. Try again later.'
                            );
                            if (in_array($e->getMessage(), $faults)) {
                                return $this->error($faults[$e->getMessage()]);
                            } else {
                                return $this->error('VAT error');
                            }
                        }

                        if (!$res) {
                            return $this->error('Bad VAT number');
                        }

                        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                        $existsTax = \Stripe\Customer::allTaxIds($user->agency->createOrGetStripeCustomer()->id);
                        $exist = false;
                        foreach ($existsTax->data as $tax) {
                            if ($tax->value == $vatNumber) {
                                $exist = true;
                            }
                        }

                        if (!$exist) {
                            foreach ($existsTax->data as $tax) {
                                \Stripe\Customer::deleteTaxId($user->agency->createOrGetStripeCustomer()->id, $tax->id, []);
                            }
                            \Stripe\Customer::createTaxId(
                                $user->agency->createOrGetStripeCustomer()->id,
                                [
                                    'type' => 'eu_vat',
                                    'value' => $vatNumber,
                                ]
                            );
                            if($options['address']['country'] != 'EE') {
                                $user->agency->updateStripeCustomer(['tax_exempt' => 'reverse']);
                            }
                        }
                    } else {
                        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                        $existsTax = \Stripe\Customer::allTaxIds($user->agency->createOrGetStripeCustomer()->id);
                        foreach ($existsTax->data as $tax) {
                            \Stripe\Customer::deleteTaxId($user->agency->createOrGetStripeCustomer()->id, $tax->id, []);
                        }
                    }
                } elseif ($request->get('client_type') == 'physical') {
                    $options = [];
                    $options['name'] = $request->name;
                    $options['email'] = $request->email;
                    $options['address']['city'] = $request->city;
                    $options['address']['state'] = $request->state;
                    $options['address']['postal_code'] = $request->postal_code;
                    $options['address']['country'] = $request->country;
                    $options['metadata']['address'] = $request->address;
                    $options['metadata']['real_name'] = $request->name;
                    $options['tax_exempt'] = 'none';
                    $user->agency->updateStripeCustomer($options);
                    $user->agency->client_type = 'physical';
                    $user->agency->save();
                }
            }

        }

        if($request->has('promocode')) {

            $stripe = new \Stripe\StripeClient(
                env('STRIPE_SECRET')
            );

            $stripeCode = $stripe->promotionCodes->all(['code' => $request->promocode]);

            if(!count($stripeCode->data)) {
                return $this->error(__('messages.coupon_not_found'));
            }

            if(!$stripeCode->data[0]->active) {
                return $this->error(__('messages.coupon_not_found'));
            }

            $promo = $stripe->coupons->retrieve($stripeCode->data[0]->coupon->id, [ ['expand' => ['applies_to']]]);

            if(!$promo) {
                return $this->error(__('messages.coupon_not_found'));
            }

            if(!isset($promo->applies_to->products) || !count($promo->applies_to->products)) {
                return $this->error(__('messages.coupon_not_found'));
            }

            if(!in_array($plan->stripe_id, $promo->applies_to->products)) {
                return $this->error(__('messages.coupon_not_found'));
            }

            $user->agency
                ->newSubscription($plan->stripe_name, $planPrice['stripe_price_id'])
                ->withPromotionCode($stripeCode->data[0]->id)
                ->create($request->token);
        } else {
            $user->agency
                ->newSubscription($plan->stripe_name, $planPrice['stripe_price_id'])
                ->create($request->token);
        }


        if ($plan->stripe_name == 'HRBLADE Enterprise') {
            $metered = PlanStripe::where('active', 1)->where('extra', '>', 0)->first();
            if ($metered) {
                if (!$user->agency->subscribed($metered->stripe_name)) {
                    $meterPrice = $metered->prices[0];
                    foreach ($metered->prices as $meter) {
                        if ($planPrice['currency'] == $meter['currency']) {
                            $meterPrice = $meter;
                            break;
                        }
                    }

                    $user->agency->newSubscription($metered->stripe_name, [])
                        ->meteredPlan($meterPrice['stripe_price_id'])
                        ->create($request->token);
                }
            }
        }



        return $this->success($plan, __('messages.subscribed'));
    }

    public function swap(Request $request)
    {
        $plan = PlanStripe::where('id', $request->get('plan_id'))->where('active', 1)->where('price', '>', 0)->where('extra', 0)->first();

        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

        $active = $user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();

        if (!$active) {
            return $this->error(__('messages.active_plan_not_found'));
        }

        if (!in_array($request->price_id, collect($plan->prices)->pluck('stripe_price_id')->toArray())) {
            return $this->error(__('messages.price_not_found'));
        }

        $planPrice = false;

        foreach ($plan->prices as $price) {
            if ($price['stripe_price_id'] == $request->price_id) {
                $planPrice = $price;
                break;
            }
        }


        config(['cashier.currency' => $planPrice['currency']]);

        $user->agency
            ->subscription($active->name)
            ->swapAndInvoice($planPrice['stripe_price_id']);

        return $this->success($plan, __('messages.subscribed'));
    }

    public function subscribeCancel(Request $request)
    {

        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

        if ($user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first()) {
            $user->agency->subscription($user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first()->name)->cancelNow();
            $metered = PlanStripe::where('active', 1)->where('extra', 1)->first();
            if ($metered) {
                if ($user->agency->subscribed($metered->stripe_name)) {
                    $user->agency->subscription($metered->stripe_name)->cancelNow();
                }
            }
        }

        return $this->success([], __('messages.plan_canceled'));
    }

    public function customerUpdate(Request $request)
    {
            $user = Auth::user();

            if (!$user->isOwner()) {
                return $this->error(__('messages.only_owners_can'));
            }

            if ($request->has('name') && $request->has('email')) {
                if ($request->has('client_type')) {
                    if ($request->get('client_type') == 'business') {
                        $options = [];
                        $options['name'] = $request->name;
                        $options['email'] = $request->email;
                        $options['address']['city'] = $request->city;
                        $options['address']['state'] = $request->state;
                        $options['address']['postal_code'] = $request->postal_code;
                        $options['address']['country'] = $request->country;
                        $options['address']['line1'] = $request->name;
                        $options['metadata']['address'] = $request->address;
                        $options['metadata']['real_name'] = $request->real_name;
                        $options['tax_exempt'] = 'none';
                        $user->agency->createOrGetStripeCustomer();
                        $user->agency->updateStripeCustomer($options);
                        $user->agency->client_type = 'business';
                        $user->agency->save();

                        if ($request->has('vat')) {
                            $vatNumber = preg_replace('/[^a-zA-Z0-9]/', '', $request->vat);
                            $validator = new Validator();
                            try {
                                $res = $validator->validateVatNumber($vatNumber);
                            } catch (\Exception $e) {
                                $faults = array (
                                    'INVALID_INPUT'       => 'The provided CountryCode is invalid or the VAT number is empty',
                                    'SERVICE_UNAVAILABLE' => 'The SOAP service is unavailable, try again later',
                                    'MS_UNAVAILABLE'      => 'The Member State service is unavailable, try again later or with another Member State',
                                    'TIMEOUT'             => 'The Member State service could not be reached in time, try again later or with another Member State',
                                    'SERVER_BUSY'         => 'The service cannot process your request. Try again later.'
                                );
                                if(in_array($e->getMessage(), $faults)) {
                                    return $this->error($faults[$e->getMessage()]);
                                } else {
                                    return $this->error('VAT error');
                                }
                            }

                            if(!$res) {
                                return $this->error('Bad VAT number');
                            }

                            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                            $existsTax = \Stripe\Customer::allTaxIds($user->agency->createOrGetStripeCustomer()->id);
                            $exist = false;
                            foreach ($existsTax->data as $tax) {
                                if ($tax->value == $vatNumber) {
                                    $exist = true;
                                }
                            }

                            if (!$exist) {
                                foreach ($existsTax->data as $tax) {
                                   \Stripe\Customer::deleteTaxId($user->agency->createOrGetStripeCustomer()->id, $tax->id, []);
                                }
                                \Stripe\Customer::createTaxId(
                                    $user->agency->createOrGetStripeCustomer()->id,
                                    [
                                        'type' => 'eu_vat',
                                        'value' => $vatNumber,
                                    ]
                                );
                                if($options['address']['country'] != 'EE') {
                                    $user->agency->updateStripeCustomer(['tax_exempt' => 'reverse']);
                                }
                            }
                        } else {
                            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                            $existsTax = \Stripe\Customer::allTaxIds($user->agency->createOrGetStripeCustomer()->id);
                            foreach ($existsTax->data as $tax) {
                                \Stripe\Customer::deleteTaxId($user->agency->createOrGetStripeCustomer()->id, $tax->id, []);
                            }
                        }
                    } elseif ($request->get('client_type') == 'physical') {
                        $options = [];

                        $options['name'] = $request->name;
                        $options['email'] = $request->email;
                        $options['address']['city'] = $request->city;
                        $options['address']['state'] = $request->state;
                        $options['address']['postal_code'] = $request->postal_code;
                        $options['address']['country'] = $request->country;
                        $options['metadata']['address'] = $request->address;
                        $options['metadata']['real_name'] = $request->name;
                        $options['tax_exempt'] = 'none';
                        $user->agency->createOrGetStripeCustomer();
                        $user->agency->updateStripeCustomer($options);
                        $user->agency->client_type = 'physical';
                        $user->agency->save();
                    }
                }
            }

            return $this->success([], 'updated');
    }

    public function vatValidate(Request $request)
    {
        $vatNumber = preg_replace('/[^a-zA-Z0-9]/', '', $request->vat);
        $validator = new Validator();
        try {
            $res = $validator->validateVatNumber($vatNumber);
        } catch (\Exception $e) {
            $faults = array (
                'INVALID_INPUT'       => 'The provided CountryCode is invalid or the VAT number is empty',
                'SERVICE_UNAVAILABLE' => 'The SOAP service is unavailable, try again later',
                'MS_UNAVAILABLE'      => 'The Member State service is unavailable, try again later or with another Member State',
                'TIMEOUT'             => 'The Member State service could not be reached in time, try again later or with another Member State',
                'SERVER_BUSY'         => 'The service cannot process your request. Try again later.'
            );
            if(in_array($e->getMessage(), $faults)) {
                return $this->error($faults[$e->getMessage()]);
            } else {
                return $this->error('Bad VAT error');
            }
        }

        if(!$res) {
            return $this->error('Bad VAT number');
        }

        return $this->success($res);
    }

    public function changePaymentMethod(Request $request)
    {
        $user = Auth::user();
        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();
        $stripePlan = $user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();
        if ($stripePlan) {
            if (!$stripePlan->stripe_id == 'manually') {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                $paymentMethod = $request->token;
                if($paymentMethod) {
                    \Stripe\Subscription::update(
                        $stripePlan->stripe_id,
                        [
                            'default_payment_method' => $paymentMethod,
                        ]
                    );
                }
                return $this->success([], __('messages.payment_method_changed'));

            }
        }
        return $this->error('Plan not found');
    }

    public function taxes()
    {
        return $this->success(Tax::where('active',1)->select(['country', 'id','percent'])->get());
    }

    public function checkPromocode(Request $request)
    {
        $plan = PlanStripe::where('id', $request->get('plan_id'))->where('active', 1)->where('price', '>', 0)->where('extra', 0)->first();

        if (!$plan) {
            return $this->error(__('messages.plan_not_found'));
        }

        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

        if ($user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first()) {
            return $this->error(__('messages.active_plan_exist'));
        }


        if (!in_array($request->price_id, collect($plan->prices)->pluck('stripe_price_id')->toArray())) {
            return $this->error(__('messages.price_not_found'));
        }


        $planPrice = false;


        foreach ($plan->prices as $price) {
            if ($price['stripe_price_id'] == $request->price_id) {
                $planPrice = $price;
                break;
            }
        }

        if(!$planPrice) {
            return $this->error(__('messages.coupon_not_found'));
        }

        config(['cashier.currency' => $price['currency']]);

        if(!$request->promocode) {
            return $this->error(__('messages.coupon_not_found'));
        }

        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET')
        );

        $stripeCode = $stripe->promotionCodes->all(['code' => $request->promocode]);

        if(!count($stripeCode->data)) {
            return $this->error(__('messages.coupon_not_found'));
        }

        if(!$stripeCode->data[0]->active) {
            return $this->error(__('messages.coupon_not_found'));
        }

        $promo = $stripe->coupons->retrieve($stripeCode->data[0]->coupon->id, [ ['expand' => ['applies_to']]]);

        if(!$promo) {
            return $this->error(__('messages.coupon_not_found'));
        }

        if(!isset($promo->applies_to->products) || !count($promo->applies_to->products)) {
            return $this->error(__('messages.coupon_not_found'));
        }

        if(!in_array($plan->stripe_id, $promo->applies_to->products)) {
            return $this->error(__('messages.coupon_not_found'));
        }

        if($promo->percent_off > 0) {
            $planPrice['percent_off'] = $promo->percent_off;
            $planPrice['amount_off'] = null;
            $planPrice['price'] = round($planPrice['price'] - ($planPrice['price'] / 100 * $promo->percent_off));
            return $this->success($planPrice);
        } elseif ($promo->amount_off > 0) {
            $planPrice['percent_off'] = null;
            $planPrice['amount_off'] = $promo->amount_off;
            $planPrice['price'] = round($planPrice['price'] - $promo->amount_off);
            return $this->success($planPrice);
        }

        return $this->error(__('messages.coupon_not_found'));
    }

    public function createCheckoutPage(Request  $request) {

        $plan = PlanStripe::where('id', $request->get('plan_id'))->where('active', 1)->where('price', '>', 0)->where('extra', 0)->first();

        if (!$plan) {
            return $this->error(__('messages.plan_not_found'));
        }

        $user = Auth::user();

        if (!$user->isOwner()) {
            return $this->error(__('messages.only_owners_can'));
        }

        $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

        if ($user->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first()) {
            return $this->error(__('messages.active_plan_exist'));
        }


        if (!in_array($request->price_id, collect($plan->prices)->pluck('stripe_price_id')->toArray())) {
            return $this->error(__('messages.price_not_found'));
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $request->price_id,
                'quantity' => 1,
            ]],
            'automatic_tax' => [
                'enabled' => true,
            ],
            'tax_id_collection' => [
                'enabled' => true,
            ],
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'required',
            'mode' => 'subscription',
            'success_url' => 'https://test.hrblade.com' . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://test.hrblade.com',
        ]);

        return $this->success($session->url);
    }
}
