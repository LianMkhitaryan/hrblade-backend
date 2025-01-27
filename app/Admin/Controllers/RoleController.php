<?php

namespace App\Admin\Controllers;

use App\Models\DefaultQuestion;
use App\Models\Industry;
use App\Models\Role;
use App\Models\RoleCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\App;

class RoleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Роли';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Role());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('active', __('Active'));
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function($filter){
            $filter->like('name', 'name');
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
        $show = new Show(Role::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('active', __('Active'));
        $show->field('industry_id', __('Industry id'));
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
        $form = new Form(new Role());

        $form->text('name', "Name")->rules('required');
        $form->summernote('description')->rules('required');
        $form->switch('active', __('Active'));
        $form->select('industry_id', 'Industry')->options(Industry::all()->map(function ($item) {
            $item->show_name = $item->getName();
            return $item;
        })->pluck('show_name','id'));

        $form->select('role_category_id', 'Role Category')->options(RoleCategory::all()->map(function ($item) {
            $item->show_name = $item->name;
            return $item;
        })->pluck('show_name','id'));

        $form->multipleSelect('questions','Question')->options(function (){
            $questions = DefaultQuestion::all();
            foreach ($questions as $question) {
                $question->name1 = $question->name . " ($question->id) $question->language";
            }

            return $questions->pluck('name1','id');
        });

        return $form;
    }
}
