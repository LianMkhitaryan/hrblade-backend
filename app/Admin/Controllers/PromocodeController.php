<?php

namespace App\Admin\Controllers;

use App\Models\PlanStripe;
use App\Models\Promocode;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PromocodeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Promocode';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Promocode());

        $grid->column('id', __('Id'));
        $grid->column('active', __('Active'));
        $grid->column('code', __('Code'));
        $grid->column('end_at', __('End at'));

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
        $show = new Show(Promocode::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('plan_id', __('Plan id'));
        $show->field('stripe_id', __('Stripe id'));
        $show->field('active', __('Active'));
        $show->field('code', __('Code'));
        $show->field('discount', __('Discount'));
        $show->field('end_at', __('End at'));
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
        $form = new Form(new Promocode());

        $form->select('plan_id', 'Plan')->options(PlanStripe::all()->pluck('name','id'));
        $form->text('stripe_id', __('Stripe id'));
        $form->switch('active', __('Active'));
        $form->text('code', __('Code'));
        $form->table('discount', function ($table) {
            $table->decimal('amount', __('Price'));
            $table->text('currency', __('Currency'));
            $table->text('symbol');
        });
        $form->datetime('end_at', __('End at'));

        return $form;
    }
}
