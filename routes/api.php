<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/interview/{hash}', [\App\Http\Controllers\Api\InterviewController::class, 'page']);
Route::post('/interview/block/{hash}', [\App\Http\Controllers\Api\InterviewController::class, 'block']);
Route::get('/interview/response/{hash}', [\App\Http\Controllers\Api\InterviewController::class, 'response']);

Route::get('/room/{hash}', [\App\Http\Controllers\Api\RoomsController::class, 'get']);

Route::get('/company/{hash}', [\App\Http\Controllers\Api\CompaniesController::class, 'getCompanyByHash']);

Route::post('/response', [\App\Http\Controllers\Api\ResponsesController::class, 'create']);

Route::post('/login', [\App\Http\Controllers\Api\UsersController::class, 'login']);

Route::post('/login/zapier', [\App\Http\Controllers\Api\ZapierController::class, 'login']);


Route::post('/register', [\App\Http\Controllers\Api\UsersController::class, 'register']);
Route::post('/register/{hash}', [\App\Http\Controllers\Api\UsersController::class, 'registerByLink']);

Route::post('/forgot', [\App\Http\Controllers\Api\UsersController::class, 'sendResetLink']);
Route::post('/reset-password', [\App\Http\Controllers\Api\UsersController::class, 'resetPassword']);
Route::get('/job/export/{id}/{token}', [\App\Http\Controllers\Api\JobsController::class, 'export']);

Route::post('/invitev2/result/{hash}', [\App\Http\Controllers\Api\UsersController::class, 'inviteCompanyResult']);
Route::get('/invitev2/get/{hash}', [\App\Http\Controllers\Api\PermissionsController::class, 'getInvite']);

Route::get('/config', [\App\Http\Controllers\Api\BaseController::class, 'config']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/zapier/me', [\App\Http\Controllers\Api\ZapierController::class, 'me']);
    Route::get('/zapier/responses', [\App\Http\Controllers\Api\ZapierController::class, 'responses']);

    Route::post('/zapier/subscribe', [\App\Http\Controllers\Api\ZapierController::class, 'subscribe']);
    Route::post('/zapier/invite', [\App\Http\Controllers\Api\ZapierController::class, 'invite']);
    Route::post('/zapier/jobs', [\App\Http\Controllers\Api\ZapierController::class, 'jobs']);
    Route::post('/zapier/unsubscribe', [\App\Http\Controllers\Api\ZapierController::class, 'unsubscribe']);

    Route::get('/start', [\App\Http\Controllers\Api\DashboardController::class, 'start']);

    Route::get('/user', [\App\Http\Controllers\Api\UsersController::class, 'getUser']);
    Route::post('/logout', [\App\Http\Controllers\Api\UsersController::class, 'logout']);
    Route::post('/user/settings', [\App\Http\Controllers\Api\UsersController::class, 'settings']);
    Route::post('/create/token', [\App\Http\Controllers\Api\UsersController::class, 'token']);

    Route::get('/companies', [\App\Http\Controllers\Api\CompaniesController::class, 'companies']);
    Route::post('/company/create', [\App\Http\Controllers\Api\CompaniesController::class, 'create']);
    Route::post('/company/update', [\App\Http\Controllers\Api\CompaniesController::class, 'update']);
    Route::get('/company/get/{id}', [\App\Http\Controllers\Api\CompaniesController::class, 'company']);
    Route::post('/company/remove/{id}', [\App\Http\Controllers\Api\CompaniesController::class, 'remove']);

    Route::get('/jobs', [\App\Http\Controllers\Api\JobsController::class, 'jobs']);
    Route::post('/job/create', [\App\Http\Controllers\Api\JobsController::class, 'create']);
    Route::post('/job/update', [\App\Http\Controllers\Api\JobsController::class, 'update']);
    Route::post('/job/active', [\App\Http\Controllers\Api\JobsController::class, 'active']);
    Route::get('/job/get/{id}', [\App\Http\Controllers\Api\JobsController::class, 'job']);
    Route::post('/job/remove/{id}', [\App\Http\Controllers\Api\JobsController::class, 'remove']);
    Route::get('/job/exports/videos/{id?}', [\App\Http\Controllers\Api\JobsController::class, 'exportVideos']);

    Route::get('/role/categories', [\App\Http\Controllers\Api\JobsController::class, 'defaultQuestionsCategories']);
    Route::get('/role/categories/{id}', [\App\Http\Controllers\Api\JobsController::class, 'defaultQuestionsByCategory']);
    Route::get('/role/questions/{id}', [\App\Http\Controllers\Api\JobsController::class, 'questions']);

    Route::get('/users', [\App\Http\Controllers\Api\UsersController::class, 'all']);

    Route::get('/faqs', [\App\Http\Controllers\Api\FaqsController::class, 'all']);
    Route::get('/plans', [\App\Http\Controllers\Api\PlansController::class, 'all']);
    Route::get('/plans/current', [\App\Http\Controllers\Api\PlansController::class, 'current']);
    Route::post('/plans/cancel', [\App\Http\Controllers\Api\PlansController::class, 'cancelSubscription']);
    Route::post('/plans/quantity', [\App\Http\Controllers\Api\PlansController::class, 'changeQuantity']);

    Route::post('/help', [\App\Http\Controllers\Api\HelpsController::class, 'create']);

    Route::post('/job/invite/create', [\App\Http\Controllers\Api\InviteController::class, 'create']);
    Route::post('/job/invite/create/csv', [\App\Http\Controllers\Api\InviteController::class, 'createFromCSV']);

    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'get']);

    Route::post('/video/rate', [\App\Http\Controllers\Api\ResponsesController::class, 'videoRate']);

    Route::get('/response/get/{id}', [\App\Http\Controllers\Api\ResponsesController::class, 'get']);
    Route::post('/response/remove/{id}', [\App\Http\Controllers\Api\ResponsesController::class, 'remove']);
    Route::post('/response/add/note', [\App\Http\Controllers\Api\ResponsesController::class, 'note']);
    Route::post('/response/add/rating', [\App\Http\Controllers\Api\ResponsesController::class, 'rating']);
    Route::post('/response/change/status', [\App\Http\Controllers\Api\ResponsesController::class, 'status']);
    Route::post('/response/add/comment', [\App\Http\Controllers\Api\ResponsesController::class, 'comment']);
    Route::post('/response/comments/add', [\App\Http\Controllers\Api\CommentsController::class, 'create']);
    Route::post('/response/scores/add', [\App\Http\Controllers\Api\CompetencesController::class, 'createScore']);
    Route::post('/response/comments/remove/{id}', [\App\Http\Controllers\Api\CommentsController::class, 'remove']);
    Route::post('/response/change/pipeline', [\App\Http\Controllers\Api\ResponsesController::class, 'changePipeline']);
    Route::post('/response/send/invite/{id}', [\App\Http\Controllers\Api\ResponsesController::class, 'sendInvite']);

    Route::post('/invite/create', [\App\Http\Controllers\Api\UsersController::class, 'invite']);
    Route::post('/invitev2/create', [\App\Http\Controllers\Api\UsersController::class, 'inviteV2']);

    Route::get('/permissions/user', [\App\Http\Controllers\Api\PermissionsController::class, 'get']);
    Route::post('/permissions/user', [\App\Http\Controllers\Api\PermissionsController::class, 'set']);

    Route::post('/plans/invoice', [\App\Http\Controllers\Api\PlansController::class, 'sendEmail']);

    Route::post('/user/remove/{id}', [\App\Http\Controllers\Api\UsersController::class, 'remove']);

    Route::get('/rooms', [\App\Http\Controllers\Api\RoomsController::class, 'rooms']);
    Route::post('/rooms/create', [\App\Http\Controllers\Api\RoomsController::class, 'create']);
    Route::post('/rooms/edit', [\App\Http\Controllers\Api\RoomsController::class, 'edit']);
    Route::post('/rooms/delete', [\App\Http\Controllers\Api\RoomsController::class, 'delete']);

    Route::post('/templates/preview', [\App\Http\Controllers\Api\TemplatesController::class, 'get']);
    Route::post('/templates/default', [\App\Http\Controllers\Api\TemplatesController::class, 'getDefault']);
    Route::get('/templates/{id}', [\App\Http\Controllers\Api\TemplatesController::class, 'index']);
    Route::post('/templates/edit', [\App\Http\Controllers\Api\TemplatesController::class, 'edit']);
    Route::post('/templates/delete', [\App\Http\Controllers\Api\TemplatesController::class, 'delete']);
    Route::post('/templates/send', [\App\Http\Controllers\Api\TemplatesController::class, 'sendTemplate']);

    Route::get('/competenses/sets/{lang?}', [\App\Http\Controllers\Api\CompetencesController::class, 'sets']);

    Route::get('/v2/plans', [\App\Http\Controllers\Api\StripeController::class, 'plans']);
    Route::get('/v2/plans/current', [\App\Http\Controllers\Api\StripeController::class, 'current']);
    Route::post('/v2/plans/intent', [\App\Http\Controllers\Api\StripeController::class, 'createIntent']);
    Route::post('/v2/plans/create/form', [\App\Http\Controllers\Api\StripeController::class, 'createCheckoutPage']);
    Route::post('/v2/plans/subscribe', [\App\Http\Controllers\Api\StripeController::class, 'subscribe']);
    Route::post('/v2/plans/swap', [\App\Http\Controllers\Api\StripeController::class, 'swap']);
    Route::post('/v2/plans/cancel', [\App\Http\Controllers\Api\StripeController::class, 'subscribeCancel']);
    Route::post('/v2/plans/customer/update', [\App\Http\Controllers\Api\StripeController::class, 'customerUpdate']);
    Route::get('/v2/plans/vat/validate', [\App\Http\Controllers\Api\StripeController::class, 'vatValidate']);
    Route::post('/v2/plans/method/change', [\App\Http\Controllers\Api\StripeController::class, 'changePaymentMethod']);
    Route::get('/v2/plans/taxes', [\App\Http\Controllers\Api\StripeController::class, 'taxes']);
    Route::get('/v2/plans/promocode', [\App\Http\Controllers\Api\StripeController::class, 'checkPromocode']);
});
