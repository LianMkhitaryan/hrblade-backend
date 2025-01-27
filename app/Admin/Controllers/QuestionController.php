<?php

namespace App\Admin\Controllers;

use App\Models\Job;
use App\Models\Question;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class QuestionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Вопросы';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Question());

        $grid->column('id', __('Id'));
        $grid->column('question', __('Name'))->table(['language' => 'key', 'question' => 'value']);
        $grid->column('time', __('Time'));
        $grid->column('job_id', __('Job id'));

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
        $show = new Show(Question::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('question', __('Question'));
        $show->field('time', __('Time'));
        $show->field('job_id', __('Job id'));
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
        $form = new Form(new Question());

        $form->table('question', function ($table) {
            $table->select('language')->options(['ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'es' => 'ES', 'ar' => 'AR']);
            $table->text('question');
        })->rules('required|min:1');
        $form->time('time', __('Time'))->default(('00:00:00'));
        $form->select('job_id', 'Job')->options(Job::all()->pluck('name','id'));

        return $form;
    }
}
