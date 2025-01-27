<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

Encore\Admin\Form::forget(['map', 'editor']);
\Encore\Admin\Facades\Admin::css('https://fonts.gstatic.com');
\Encore\Admin\Facades\Admin::css('https://fonts.googleapis.com/css2?family=Comfortaa:wght@300;400;500;600;700&display=swa');


\Encore\Admin\Facades\Admin::style('.main-footer strong {display: none;}');
\Encore\Admin\Facades\Admin::style('.skin-blue-light .main-header .navbar{background-color:#fda94c}.skin-blue-light .main-header li.user-header{background-color:#fda94c}.skin-blue-light .main-header .logo{background-color:#fda94c;color:#fff;border-bottom:0 solid transparent}.skin-blue-light .sidebar-menu>li.active{border-left-color:#fda94c}.skin-blue.layout-top-nav .main-header>.logo{background-color:#fda94c;color:#fff;border-bottom:0 solid transparent}.skin-blue-light .main-header .logo:hover {
    background-color:#fda13c;
}.skin-blue-light .main-header .navbar .sidebar-toggle:hover {
    background-color: #fda13c;
}.box.box-info {
    border-top-color: #fda13c;
}body{
    font-family: \'Comfortaa\', cursive !important;
}h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6{font-family:\'Comfortaa\', cursive}');
