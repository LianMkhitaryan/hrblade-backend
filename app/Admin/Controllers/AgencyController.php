<?php

namespace App\Admin\Controllers;

use App\Models\Agency;
use App\Models\Plan;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AgencyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Агенства';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Agency());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('country_code', __('country_code'));

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
        $agency = Agency::findOrFail($id);
        $show = new Show($agency);

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('plan_id', __('Plan id'));
        $show->field('plan_expire', __('Plan expire'));
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
        $form = new Form(new Agency());

        $form->text('name', __('Name'));
        $form->text('country_code', __('country_code'));
        $form->select('plan_id')->options(Plan::all()->pluck('name','id'));
        $form->datetime('plan_expire', __('Plan expire'))->default(date('Y-m-d H:i:s'));

        return $form;
    }
}
