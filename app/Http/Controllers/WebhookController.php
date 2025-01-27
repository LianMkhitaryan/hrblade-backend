<?php

namespace App\Http\Controllers;

use App\Models\PlanStripe;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;
use Laravel\Cashier\Payment;
use Laravel\Cashier\Subscription;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (config('cashier.webhook.secret')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            $response = $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return $response;
        }

        return $this->missingMethod($payload);
    }

    /**
     * Handle customer subscription created.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            $data = $payload['data']['object'];

            if (! $user->subscriptions->contains('stripe_id', $data['id'])) {
                if (isset($data['trial_end'])) {
                    $trialEndsAt = Carbon::createFromTimestamp($data['trial_end']);
                } else {
                    $trialEndsAt = null;
                }

                $firstItem = $data['items']['data'][0];
                $isSinglePlan = count($data['items']['data']) === 1;

                $subscription = $user->subscriptions()->create([
                    'name' => $data['metadata']['name'] ?? $this->newSubscriptionName($payload),
                    'stripe_id' => $data['id'],
                    'stripe_status' => $data['status'],
                    'stripe_plan' => $isSinglePlan ? $firstItem['plan']['id'] : null,
                    'quantity' => $isSinglePlan && isset($firstItem['quantity']) ? $firstItem['quantity'] : null,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => null,
                ]);

                foreach ($data['items']['data'] as $item) {
                    $subscription->items()->create([
                        'stripe_id' => $item['id'],
                        'stripe_plan' => $item['plan']['id'],
                        'quantity' => $item['quantity'] ?? null,
                    ]);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Determines the name that should be used when new subscriptions are created from the Stripe dashboard.
     *
     * @param  array  $payload
     * @return string
     */
    protected function newSubscriptionName(array $payload)
    {
        return 'default';
    }

    /**
     * Handle customer subscription updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $data = $payload['data']['object'];

            $user->subscriptions->filter(function (Subscription $subscription) use ($data) {
                return $subscription->stripe_id === $data['id'];
            })->each(function (Subscription $subscription) use ($data, $user) {
                if (
                    isset($data['status']) &&
                    $data['status'] === StripeSubscription::STATUS_INCOMPLETE_EXPIRED
                ) {
                    $subscription->items()->delete();
                    $subscription->delete();

                    return;
                }

                $oldName = $subscription->name;

                $firstItem = $data['items']['data'][0];
                $isSinglePlan = count($data['items']['data']) === 1;

                // Plan...
                $subscription->stripe_plan = $isSinglePlan ? $firstItem['plan']['id'] : null;

                $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();

                foreach ($realSubscriptions as $realSubscription) {
                    foreach ($realSubscription->prices as $realPrice) {
                        if($firstItem['plan']['id'] == $realPrice['stripe_price_id']) {
                            $planName = $realSubscription->stripe_name;
                            break;
                        }
                    }
                }

                if(isset($planName)) {
                    $subscription->name = $planName;

                    if($oldName != 'HRBLADE Enterprise' && $planName == 'HRBLADE Enterprise') {
                        $metered = PlanStripe::where('active', 1)->where('extra','>', 0)->first();
                        if($metered) {
                            if(!$user->subscribed($metered->stripe_name)) {
                                foreach ($metered->prices as $meter) {
                                    if (strtoupper($firstItem['plan']['currency']) == strtoupper($meter['currency'])) {
                                        $meterPrice = $meter;
                                        break;
                                    }
                                }
                                if (isset($meterPrice)) {
                                    $user->newSubscription($metered->stripe_name, [])
                                        ->meteredPlan($meterPrice['stripe_price_id'])
                                        ->create($user->paymentMethods()->first()->id);
                                }
                            }
                        }
                    } elseif($oldName == 'HRBLADE Enterprise' && $planName != 'HRBLADE Enterprise') {
                        $metered = PlanStripe::where('active', 1)->where('extra','>', 0)->first();
                        if($metered) {
                            if ($user->subscribed($metered->stripe_name)) {
                                $user->subscription($metered->stripe_name)->cancelNow();
                            }
                        }
                    }
                }


                // Quantity...
                $subscription->quantity = $isSinglePlan && isset($firstItem['quantity']) ? $firstItem['quantity'] : null;

                // Trial ending date...
                if (isset($data['trial_end'])) {
                    $trialEnd = Carbon::createFromTimestamp($data['trial_end']);

                    if (! $subscription->trial_ends_at || $subscription->trial_ends_at->ne($trialEnd)) {
                        $subscription->trial_ends_at = $trialEnd;
                    }
                }

                // Cancellation date...
                if (isset($data['cancel_at_period_end'])) {
                    if ($data['cancel_at_period_end']) {
                        $subscription->ends_at = $subscription->onTrial()
                            ? $subscription->trial_ends_at
                            : Carbon::createFromTimestamp($data['current_period_end']);
                    } elseif (isset($data['cancel_at'])) {
                        $subscription->ends_at = Carbon::createFromTimestamp($data['cancel_at']);
                    } else {
                        $subscription->ends_at = null;
                    }
                }

                // Status...
                if (isset($data['status'])) {
                    $subscription->stripe_status = $data['status'];
                }

                $subscription->save();

                // Update subscription items...
                if (isset($data['items'])) {
                    $plans = [];

                    foreach ($data['items']['data'] as $item) {
                        $plans[] = $item['plan']['id'];

                        $subscription->items()->updateOrCreate([
                            'stripe_id' => $item['id'],
                        ], [
                            'stripe_plan' => $item['plan']['id'],
                            'quantity' => $item['quantity'] ?? null,
                        ]);
                    }

                    // Delete items that aren't attached to the subscription anymore...
                    $subscription->items()->whereNotIn('stripe_plan', $plans)->delete();
                }
            });
        }

        return $this->successMethod();
    }

    /**
     * Handle a cancelled customer from a Stripe subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $user->subscriptions->filter(function ($subscription) use ($payload) {
                return $subscription->stripe_id === $payload['data']['object']['id'];
            })->each(function ($subscription) {
                $subscription->markAsCancelled();
            });
        }

        return $this->successMethod();
    }

    /**
     * Handle customer updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerUpdated(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['id'])) {
            $user->updateDefaultPaymentMethodFromStripe();
        }

        return $this->successMethod();
    }

    /**
     * Handle deleted customer.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerDeleted(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['id'])) {
            $user->subscriptions->each(function (Subscription $subscription) {
                $subscription->skipTrial()->markAsCancelled();
            });

            $user->forceFill([
                'stripe_id' => null,
                'trial_ends_at' => null,
                'card_brand' => null,
                'card_last_four' => null,
            ])->save();
        }

        return $this->successMethod();
    }

    /**
     * Handle payment action required for invoice.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvoicePaymentActionRequired(array $payload)
    {
        if (is_null($notification = config('cashier.payment_notification'))) {
            return $this->successMethod();
        }

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            if (in_array(Notifiable::class, class_uses_recursive($user))) {
                $payment = new Payment(StripePaymentIntent::retrieve(
                    $payload['data']['object']['payment_intent'],
                    $user->stripeOptions()
                ));

                $user->notify(new $notification($payment));
            }
        }

        return $this->successMethod();
    }

    /**
     * Get the customer instance by Stripe ID.
     *
     * @param  string|null  $stripeId
     * @return \Laravel\Cashier\Billable|null
     */
    protected function getUserByStripeId($stripeId)
    {
        return Cashier::findBillable($stripeId);
    }

    /**
     * Handle successful calls on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled e', 200);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function missingMethod($parameters = [])
    {
        return new Response;
    }
}
