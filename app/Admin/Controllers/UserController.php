<?php

namespace App\Admin\Controllers;

use App\Models\Agency;
use App\Models\Plan;
use App\Models\PlanStripe;
use App\Models\User;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Laravel\Cashier\Subscription;
use Symfony\Component\Console\Input\Input;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Пользователи';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('email', __('Email'));
        $grid->column('created_at', __('Created'))->display(function (){
            return $this->created_at->format('d.m.Y');
        });
        $grid->column('Plan', __('Plan'))->display(function (){
            if($this->isOwner()) {
                $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();
                $stripePlan = $this->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();
                if ($stripePlan) {
                    $plan = PlanStripe::where('stripe_name', $stripePlan->name)->first();
                    $plan->end_plan_at = $stripePlan->ends_at;
                } else {
                    $plan = PlanStripe::where('price', 0)->first();
                    $plan->end_plan_at = null;
                }

                return $plan->name;
            }
            return '';
        });
        $grid->column('Plan end', __('Plan end'))->display(function (){
            if($this->isOwner()) {
                $realSubscriptions = PlanStripe::where('active', 1)->where('price', '>', 0)->where('extra', 0)->get();
                $stripePlan = $this->agency->subscriptions()->active()->whereIn('name', $realSubscriptions->pluck('stripe_name')->toArray())->first();
                if ($stripePlan) {
                    $plan = PlanStripe::where('stripe_name', $stripePlan->name)->first();
                    $plan->end_plan_at = $stripePlan->ends_at;
                } else {
                    $plan = PlanStripe::where('price', 0)->first();
                    $plan->end_plan_at = null;
                }

                return Carbon::parse($plan->end_plan_at)->format('d.m.Y');
            }
            return '';
        });
        $grid->column('Companies', __('Companies'))->display(function (){
            if($this->isOwner()) {
                $companies = '';
                foreach ($this->agency->companies as $company) {
                    $companies .= $company->name . " ({$company->id}), ";
                }
                return $companies;
            }
            return '';
        });

        $grid->column('Agency lang', 'Agency lang')->display(function (){
            if($this->isOwner()) {
                return $this->agency->country_code;
            }
            return '';
        });

        $grid->column('Jobs', __('Jobs count'))->display(function (){
            if($this->isOwner()) {
                $count = 0;
                foreach ($this->agency->companies as $company) {
                    $count += $company->jobs()->count();
                }
                return $count;
            }
            return '';
        });

        $grid->column('Responses', __('Responses count'))->display(function (){
            if($this->isOwner()) {
                $count = 0;
                foreach ($this->agency->companies as $company) {
                    foreach ($company->jobs as $job) {
                        $count += $job->responses()->count();
                    }
                }
                return $count;
            }
            return '';
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));
        $user = User::findOrFail($id);

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('email', __('Email'));
        $show->field('email_verified_at', __('Email verified at'));


        $show->field('profile_photo_path', __('Profile photo path'));
        $show->field('created_at', __('Created at'));
        $show->field('phone', __('Phone'));
        $show->field('role', __('Role'));
        if($user->agency) {
            $show->field('agency', $user->agency->name);
        }


        $show->field('recruiting_owner', __('Recruiting owner'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->text('name', __('Name'));
        $form->email('email', __('Email'));
        $form->datetime('email_verified_at', __('Email verified at'));
        $form->password('password', trans('admin.password'))->rules([function($value) {
            if(strlen($value) > 0 && strlen($value) < 6) {
                return false;
            }
        }]);
        $form->password('password_confirmation', trans('admin.password_confirmation'))
            ->rules('sometimes|required_with:password|same:password');

        $form->image('profile_photo_path', __('Profile photo path'));
        $form->mobile('phone', __('Phone'));
        $form->select('role', __('Role'))->options(['OWNER' => 'OWNER','ADMIN' => 'ADMIN', 'MANAGER' => 'MANAGER']);

        $form->select('plan', 'Plan')->options(PlanStripe::all()->pluck('name','id'));
        $form->datetime('plan_end', __('Plan end'));

        $form->text('agency.country_code', __('Agency country code'));

        $form->number('agency.responses_limit', __('Agency responses limit'));
        $form->number('agency.companies_limit', __('Agency companies limit'));
        $form->number('agency.interviews_limit', __('Agency interviews limit'));
        $form->number('agency.users_limit', __('Agency users limit'));
        $form->select('agency.video_definition', __('Agency video definition'))->options([
            '1351620000001-000001' => '1080p',
            '1351620000001-000010' => '720p',
            '1351620000001-000030' => '480p 4:3',
            '1351620000001-000020' => '480p 16:9',
            '1351620000001-000050' => '360p 4:3',
            '1351620000001-000040' => '360p 16:9',
            ]);

        $form->submitted(function (Form $form) {
            $form->ignore(['password_confirmation','plan','plan_end']);
        });

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            } else {
                $form->password = $form->model()->password;
            }

            if(request()->get('plan')) {
                $plan = PlanStripe::find(request()->get('plan'));
                if($plan) {
                    if($plan->stripe_name == 'free') {
                        $agency =  $form->model()->agency;
                        Subscription::where('stripe_id','manually')->where('agency_id', $agency->id)->delete();
                        $activeSubscription = Subscription::where('stripe_status', 'active')->where('agency_id', $agency->id)->where('stripe_id','!=','manually')->first();
                        if($activeSubscription) {
                            $agency->subscription($activeSubscription->name)->cancelNow();
                        }
                        $agency->plan_id = $plan->id;
                        $agency->save();
                    } else {
                        foreach ($plan->prices as $price) {
                            $priceId = $price['stripe_price_id'];
                        }
                        if($priceId) {
                            $agency =  $form->model()->agency;
                            $endDate = Carbon::parse(request()->get('plan_end'));
                            if($endDate > Carbon::now()) {
                                Subscription::where('stripe_id','manually')->where('agency_id', $agency->id)->delete();
                                $activeSubscription = Subscription::where('stripe_status', 'active')->where('agency_id', $agency->id)->where('stripe_id','!=','manually')->first();
                                if($activeSubscription) {
                                    $agency->subscription($activeSubscription->name)->cancelNow();
                                }
                                $newSubscription = new Subscription();
                                $newSubscription->agency_id = $agency->id;
                                $newSubscription->name = $plan->stripe_name;
                                $newSubscription->stripe_id = 'manually';
                                $newSubscription->stripe_status = 'active';
                                $newSubscription->stripe_plan = $priceId;
                                $newSubscription->quantity = 1;
                                $newSubscription->ends_at = $endDate;
                                $newSubscription->save();
                            }

                        }
                    }
                }
            }
        });

        return $form;
    }
}
