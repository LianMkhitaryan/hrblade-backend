<?php

namespace App\Admin\Controllers;

use App\Models\Tax;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TaxController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Tax';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Tax());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('country', __('Country'));
        $grid->column('percent', __('Percent'));
        $grid->column('stripe_id', __('Stripe id'));
        $grid->column('active', __('Active'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(Tax::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('country', __('Country'));
        $show->field('percent', __('Percent'));
        $show->field('stripe_id', __('Stripe id'));
        $show->field('active', __('Active'));
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
        $form = new Form(new Tax());

        $form->text('name', __('Name'));
        $form->text('country', __('Country'));
        $form->decimal('percent', __('Percent'));
        $form->text('stripe_id', __('Stripe id'));
        $form->switch('active', __('Active'));

        return $form;
    }
}
