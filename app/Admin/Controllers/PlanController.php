<?php

namespace App\Admin\Controllers;

use App\Models\Plan;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PlanController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Планы';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Plan());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('price', __('Price'));

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
        $show = new Show(Plan::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('price', __('Price'));
        $show->field('responses_limit', __('Responses limit'));
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
        $form = new Form(new Plan());

        $form->text('name', __('Name'));
        $form->decimal('price', __('Price'));
        $form->number('responses_limit', __('Responses limit'));
        $form->number('companies_limit', __('Companies limit'));
        $form->number('interviews_limit', __('Interviews limit'));
        $form->switch('live', __('Live'));
        $form->textarea('description', __('Description'));
        $form->list('bonuses')->rules('required|min:5');

        return $form;
    }
}
