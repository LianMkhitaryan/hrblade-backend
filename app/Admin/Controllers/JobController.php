<?php

namespace App\Admin\Controllers;

use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Role;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class JobController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Вакансии';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Job());

        $grid->column('id', __('Id'));
        $grid->column('Owner', __('Owner'))->display(function () {

            if($this->company && $this->company->agency && $this->company->agency->owner) {
                return $this->company->agency->owner->name . " ({$this->company->agency->owner->id})";
            }
        });
        $grid->column('company', __('Company'))->display(function () {
            if($this->company) {
                return $this->company->name;
            }
        });
        $grid->column('name', __('Name'));
        $grid->column('Responses count', __('Responses count'))->display(function () {
            return $this->responses()->count();
        });
        $grid->column('active', __('Active'));


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
        $show = new Show(Job::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('company_id', __('Company id'));
        $show->field('name', __('Name'));
        $show->field('active', __('Active'));
        $show->field('for_follow_up', __('For follow up'));
        $show->field('salary', __('Salary'));
        $show->field('video', __('Video'));
        $show->field('description', __('Description'));
        $show->field('industry_id', __('Industry id'));
        $show->field('role_id', __('Role id'));
        $show->field('expire_days', __('Expire days'));
        $show->field('expire_date', __('Expire date'));
        $show->field('start_at', __('Start at'));
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
        $form = new Form(new Job());

        $form->select('company_id', 'Company')->options(Company::all()->pluck('name','id'));
        $form->text('name', __('Name'));
        $form->switch('active', __('Active'));
        $form->switch('for_follow_up', __('For follow up'));
        $form->decimal('salary', __('Salary'));
        $form->text('video', __('Video'));
        $form->textarea('description', __('Description'));
        $form->select('industry_id', 'Industry')->options(Industry::all()->map(function ($item) {
            $item->show_name = $item->getName();
            return $item;
        })->pluck('show_name','id'));
        $form->select('role_id', 'Role')->options(Role::all()->map(function ($item) {
            $item->show_name = $item->getName();
            return $item;
        })->pluck('show_name','id'));
        $form->number('expire_days', __('Expire days'));
        $form->datetime('expire_date', __('Expire date'))->default(date('Y-m-d H:i:s'));
        $form->datetime('start_at', __('Start at'))->default(date('Y-m-d H:i:s'));

        $form->hasMany('questions', function (Form\NestedForm $form) {
            $form->text('question');
            $form->time('time');
        });

        return $form;
    }
}
