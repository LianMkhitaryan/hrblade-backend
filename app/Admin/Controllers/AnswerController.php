<?php

namespace App\Admin\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Response;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AnswerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Ответы';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Answer());

        $grid->column('id', __('Id'));
        $grid->column('question_id', __('Question id'));
        $grid->column('answer', __('Answer'));
        $grid->column('video', __('Video'));
        $grid->column('response_id', __('Response id'));
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
        $show = new Show(Answer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('question_id', __('Question id'));
        $show->field('answer', __('Answer'));
        $show->field('video', __('Video'));
        $show->field('response_id', __('Response id'));
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
        $form = new Form(new Answer());

        $form->select('question_id', 'Question')->options(Question::all()->pluck('question','id'));
        $form->textarea('answer', __('Answer'));
        $form->file('video', __('Video'));

        $form->select('response_id', 'Response')->options(Response::all()->pluck('full_name','id'));

        return $form;
    }
}
