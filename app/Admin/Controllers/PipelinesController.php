<?php

namespace App\Admin\Controllers;

use App\Models\Pipeline;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PipelinesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Pipeline';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Pipeline());

        $grid->model()->where('default',1);

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('language', __('Language'));
        $grid->column('sorting', __('Sorting'));

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
        $show = new Show(Pipeline::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('language', __('Language'));
        $show->field('sorting', __('Sorting'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Pipeline());

        $form->text('name', __('Name'));
        $form->select('language', __('Language'))->options(['ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'es' => 'ES']);
        $form->number('sorting', __('Sorting'))->default(1);

        $form->saving(function (Form $form) {
            $form->model()->default = 1;
        });

        return $form;
    }
}
