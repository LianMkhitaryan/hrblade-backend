<?php

namespace App\Admin\Controllers;

use App\Models\EmailTemplate;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class EmailTemplatesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'EmailTemplate';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new EmailTemplate());
        $grid->model()->where('default', 1);

        $grid->column('id', __('Id'));
        $grid->column('name', "Name");
        $grid->column('language', __('Language'));
        $grid->column('type', __('Type'));
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
        $show = new Show(EmailTemplate::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('language', __('Language'));
        $show->field('company_id', __('Company id'));
        $show->field('type', __('Type'));
        $show->field('email', __('Email'));
        $show->field('email_title', __('Email title'));
        $show->field('sms', __('Sms'));
        $show->field('default', __('Default'));
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
        $form = new Form(new EmailTemplate());

        $form->select('language', __('Language'))->options(['ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'es' => 'ES']);
        $form->textarea('name', "Name");
        $form->select('type', __('Type'))->options(['INVITE' => 'INVITE', 'ACCEPT' => 'ACCEPT', 'REJECT' => 'REJECT', 'RESPONSE' => 'RESPONSE']);
        $form->quill('email', __('Email'));
        $form->textarea('email_title', __('Email title'));
        $form->textarea('sms', __('Sms'));
        $form->switch('active', __('Active'));
        $form->switch('default', __('Default'))->default(1);

        return $form;
    }
}
