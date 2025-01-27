<?php

namespace App\Admin\Controllers;

use App\Models\Agency;
use App\Models\Company;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CompanyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Компании';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Company());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('logo', __('Logo'))->image();
        $grid->column('location', __('Location'));
        $grid->column('website', __('Website'));
        $grid->column('Owner', __('Owner'))->display(function (){
            if($this->agency->owner) {
                return $this->agency->owner->name . " ({$this->agency->owner->id})";
            }
        });

        $grid->column('Responses', __('Responses'))->display(function (){
            $jobs = $this->jobs;
            $count = 0;
            foreach ($jobs as $job) {
                $count += $job->responses()->count();
            }
            return $count;
        });

        $grid->column('Jobs', __('Jobs'))->display(function (){
            return $this->jobs->count();
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
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
        $show = new Show(Company::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('logo', __('Logo'));
        $show->field('location', __('Location'));
        $show->field('website', __('Website'));
        $show->field('agency_id', __('Agency id'));
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
        $form = new Form(new Company());

        $form->text('name', __('Name'));
        $form->image('logo', __('Logo'));
        $form->text('location', __('Location'));
        $form->text('website', __('Website'));

        $form->select('agency_id', 'Agency')->options(Agency::all()->pluck('name','id'));

        return $form;
    }
}
