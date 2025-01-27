<?php

namespace App\Admin\Controllers;

use App\Models\Job;
use App\Models\Question;
use App\Models\Response;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ResponseController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Резюме';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Response());

        $grid->column('id', __('Id'));
        $grid->column('Owner', __('Owner'))->display(function () {
            return $this->job->company->agency->owner->name . " ({$this->job->company->agency->owner->id})";
        });
        $grid->column('company', __('Company'))->display(function () {
            return $this->job->company->name;
        });
        $grid->column('Job', __('Job'))->display(function (){
            return $this->job->name;
        });
        $grid->column('status', __('Status'));
        $grid->disableCreateButton();

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
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
        $show = new Show(Response::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('job_id', __('Job id'));
        $show->field('status', __('Status'));
        $show->field('full', __('Full name'));
        $show->field('email', __('Email'));
        $show->field('phone', __('Phone'));
        $show->field('location', __('Location'));
        $show->field('rating', __('Rating'));
        $show->field('note', __('Note'));
        $show->field('comment', __('Comments'));
        $show->field('visited_at', __('Visited at'));
        $show->field('completed_at', __('Completed at'));
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
        $form = new Form(new Response());



        $form->select('job_id', 'Job')->options(Job::all()->pluck('name','id'));
        $form->select('status', __('Status'))->options(['INVITED', 'REVIEW','ACCEPTED','REJECTED']);
        $form->text('full', __('Full name'));
        $form->email('email', __('Email'));
        $form->mobile('phone', __('Phone'));
        $form->text('location', __('Location'));
        $form->starRating('rating', __('Rating'));
        $form->textarea('note', __('Note'));
        $form->textarea('comment', __('Comments'));
        $form->datetime('visited_at', __('Visited at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('completed_at', __('Completed at'))->default(date('Y-m-d H:i:s'));


        $form->hasMany('answers', function (Form\NestedForm $form) {
            if($form->getForm()->model()->job) {
                $form->select('question_id', 'Question')->options($form->getForm()->model()->job->questions->pluck('en','id'));
            } else {
                $form->select('question_id', 'Question')->options(Question::all()->pluck('en','id'));
            }


            $form->text('answer');
            $form->file('video');
        });

        return $form;
    }
}
