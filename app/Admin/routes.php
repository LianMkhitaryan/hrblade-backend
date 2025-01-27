<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('users', UserController::class);
    $router->resource('companies', CompanyController::class);
    $router->resource('default-questions', DefaultQuestionController::class);
    $router->resource('industries', IndustryController::class);
    $router->resource('jobs', JobController::class);
    $router->resource('plans', PlanController::class);
    $router->resource('questions', QuestionController::class);
    $router->resource('responses', ResponseController::class);
    $router->resource('roles', RoleController::class);
    $router->resource('agencies', AgencyController::class);
    $router->resource('answers', AnswerController::class);
    $router->resource('faqs', FaqController::class);
    $router->resource('helps', HelpController::class);
    $router->resource('email-templates', EmailTemplatesController::class);
    $router->resource('sets', SetsController::class);
    $router->resource('plan-stripes', StripeController::class);
    $router->resource('taxes', TaxController::class);
    $router->resource('promocodes', PromocodeController::class);
    $router->resource('pipelines', PipelinesController::class);
    $router->post('/default-questions/import/xlsx', 'DefaultQuestionController@import')->name('default-questions.import');
});
