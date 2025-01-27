<?php

namespace App\Admin\Controllers;

use App\Models\Set;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SetsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Set';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Set());

        $grid->column('id', __('Id'));
        $grid->column('language', __('Language'));
        $grid->column('name', __('Name'));
        $grid->column('active', __('Active'));
        $grid->column('default', __('Default'));

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
        $show = new Show(Set::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('competences', __('Competences'));
        $show->field('active', __('Active'));
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
        $form = new Form(new Set());




        $form->select('language')->options(['ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'es' => 'ES'])->required();
        $form->text('name', __('Name'))->required();
        $form->table('competences', function ($table) {
            $table->text('name');
            $table->number('score')->default(0);
            $table->number('sort')->default(0);
        });
        $form->switch('active', __('Active'))->default(1);
        $form->switch('default', __('Default'));

        return $form;
    }
}
