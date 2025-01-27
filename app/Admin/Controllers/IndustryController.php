<?php

namespace App\Admin\Controllers;

use App\Models\Industry;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class IndustryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Индустрии';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Industry());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->table(['language' => 'key', 'name' => 'value']);
        $grid->column('active', __('Active'));

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
        $show = new Show(Industry::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
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
        $form = new Form(new Industry());

        $form->table('name', function ($table) {
            $table->select('language')->options(['ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'es' => 'ES']);
            $table->text('name');
        })->rules('required|min:1');

//        $form->text('name', __('Name'));
        $form->switch('active', __('Active'));

        return $form;
    }
}
