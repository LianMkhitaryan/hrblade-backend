<?php

namespace App\Admin\Controllers;

use App\Models\Help;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class HelpController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Помощь';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Help());

        $grid->column('id', __('Id'));
       // $grid->column('user_id', __('User id'));
        $grid->column('email', __('Email'));
        $grid->column('subject', __('Subject'));
        $grid->column('description', __('Description'));
        $grid->column('status', __('Status'));
        $grid->column('answer', __('Answer'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(Help::findOrFail($id));

        $show->field('id', __('Id'));
      //  $show->field('user_id', __('User id'));
        $show->field('email', __('Email'));
        $show->field('subject', __('Subject'));
        $show->field('description', __('Description'));
        $show->field('status', __('Status'));
        $show->field('answer', __('Answer'));
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
        $form = new Form(new Help());

        //$form->number('user_id', __('User id'));
        $form->email('email', __('Email'));
        $form->text('subject', __('Subject'));
        $form->textarea('description', __('Description'));
        $form->select('status', __('Status'))->options(['NEW' => 'New', 'ANSWERED' => 'Answered']);
        $form->textarea('answer', __('Answer'));

        return $form;
    }
}
