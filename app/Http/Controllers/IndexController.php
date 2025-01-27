<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Plan;
use App\Models\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Stripe\Subscription;

class IndexController extends Controller
{
    public function index()
    {
        $view = false;
        return view('landing.index',compact('view'));
    }

    public function plan($id)
    {
        $plan = Plan::find($id);


        $intent =  auth()->user()->agency->createSetupIntent();

        $pro = Plan::where('id', env('PRO_ID'))->first();
        View::share('pro', $pro);
        $starter = Plan::where('id', env('STARTER_ID'))->first();
        View::share('starter', $starter);
        $free = Plan::where('id', env('FREE_ID'))->first();
        View::share('free', $free);

        $title = $plan->name;
        $view = false;

        return view("landing.plan", compact('view','title', 'plan', 'intent'));
    }

    public function subscribe(Request $request, Plan $plan)
    {
        $plan = Plan::findOrFail($request->get('plan'));

        $request->user()->agency
            ->newSubscription($plan->stripe_name, $plan->stripe_price)
            ->create($request->token);

        return redirect()->route('page', 'pricing')->with('status', 'Your plan subscribed successfully');
    }

    public function subscribeSwap(Request $request, Plan $plan)
    {
        $plan = Plan::findOrFail($request->get('plan'));

        $request->user()->agency
            ->subscription(auth()->user()->agency->subscriptions()->first()->name)
            ->swapAndInvoice($plan->stripe_price);

        return redirect()->route('page', 'pricing')->with('status', 'Your plan subscribed successfully');
    }

    public function subscribeCancel(Request $request)
    {
        if(auth()->user()->agency->subscriptions()->first()) {
            $request->user()->agency->subscription(auth()->user()->agency->subscriptions()->first()->name)->cancelNow();
        }

        return redirect()->route('page', 'pricing')->with(['status' => 'Your plan canceled']);
    }


    public function page($view)
    {
        $viewRender = "landing.{$view}";

        if (!view()->exists($viewRender)) {
            abort(404);
        }

        if(Auth::user() && ($view == 'login' || $view == 'register')) {
            if(Auth::user()->email_verified_at){

                return redirect()->to(route('dashboardapp'));
            }
            $title = 'Check email for confirm';
            return view("landing.mail", compact('view','title'));
        }

        switch($view) {
            case 'about':
                $title = 'About us';
                break;
            case 'contacts':
                $title = 'Contacts';
                break;
            case 'faq':
                $title = 'FAQ';
                break;
            case 'features':
                $title = 'Features';
                break;
            case 'login':
                $title = 'Login';
                break;
            case 'password':
                $title = 'Forget password';
                break;
            case 'pricing':
                $pro = Plan::where('id', env('PRO_ID'))->first();
                View::share('pro', $pro);
                $starter = Plan::where('id', env('STARTER_ID'))->first();
                View::share('starter', $starter);
                $free = Plan::where('id', env('FREE_ID'))->first();
                View::share('free', $free);
                $title = 'Pricing';
                break;
            case 'privacy':
                $title = 'Privacy policy';
                break;
            case 'refund':
                $title = 'Refund';
                break;
            case 'register':
                $title = 'Register';
                break;
            case 'register-hash':
                $title = 'Register';
                break;
            case 'reset':
                $title = 'Reset password';
                break;
            case 'terms':
                $title = 'Terms';
                break;
            default:
                $title = 'Video interviewing';
                break;
        }

        return view($viewRender, compact('view','title'));
    }

    public function reset($token)
    {
        $title = 'Reset password';

        $view = false;
        return view("landing.reset", compact('token','view', 'title'));
    }

    public function dashboard()
    {
        $user = Auth::user();

        if(!$user) {
            Auth::logout();
            return redirect()->to(route('index'));
        }

        $token = $user->createToken('intrewoo');
        setcookie("token-app", $token->plainTextToken, time()+2, "", "interwoo.com", 0, false);

        return redirect()->away(env('APP_PAGE'));
    }

    public function logout(Request $request)
    {
        if (isset($_COOKIE['appart'])) {
            unset($_COOKIE['appart']);
            setcookie('appart', null, -1, '/');
        }

        if (isset($_COOKIE['token-app'])) {
            unset($_COOKIE['token-app']);
            setcookie('token-app', null, -1, '/');
        }

        Auth::logout();

        $request->session()->invalidate();

        return redirect()->to(route('index'));
    }
}
