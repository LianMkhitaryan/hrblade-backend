<?php

namespace App\Admin\Controllers;

use App\Models\PlanStripe;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StripeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'PlanStripe';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PlanStripe());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('currency', __('Currency'));
        $grid->column('description', __('Description'));
        $grid->column('stripe_name', __('Stripe name'));
        $grid->column('stripe_price', __('Stripe price'));

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
        $show = new Show(PlanStripe::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('currency', __('Currency'));
        $show->field('description', __('Description'));
        $show->field('stripe_name', __('Stripe name'));
        $show->field('stripe_price', __('Stripe price'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PlanStripe());

        $form->text('name', __('Name'));
        $form->switch('active', __('Active'))->default(1);
        $form->table('prices', function ($table) {
            $table->decimal('price', __('Price'));
            $table->text('stripe_price_id', __('Stripe price id'));
            $table->text('currency', __('Currency'));
            $table->text('symbol');
            $table->switch('yearly');
        });
        $form->number('users_limit', 'Users limit');
        $form->number('interviews_limit', 'Interviews limit');
        $form->number('responses_limit', 'Responses limit');
        $form->number('questions_limit', 'Video questions limit');
        $form->number('copyscape_limit', 'Copyscape limit');
        $form->switch('branding', 'Branding');
        $form->switch('email_invites', 'Email invites');
        $form->switch('sms_invites', 'Sms invites');
        $form->switch('bulk_invites', 'Bulk invites');
        $form->switch('questions_databases', 'Questions Database');
        $form->switch('export', 'Export');
        $form->number('companies_limit', 'Companies limit');
        $form->switch('zapier', 'Zapier Integration');
        $form->switch('api', 'API Integrations');
        $form->switch('live', 'Live Interview');
        $form->switch('ai_assist', 'AI Assistant');
        $form->textarea('description', __('Description'));
        $form->text('stripe_name', __('Stripe name'));
        $form->switch('extra', 'Extra package');

        return $form;
    }
}
