<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AzureHelper;
use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Role;
use App\Models\RoleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class BaseController extends Controller
{
    public $successStatus = 200;
    public $errorStatus = 400;

    public function __construct()
    {
        if(\request()->header('Accept-Language') && in_array(\request()->header('Accept-Language'),['ru','en','es','de'])) {
            App::setLocale(\request()->header('Accept-Language'));
        } else {
            App::setLocale('en');
        }
    }

    public function success($data, $message = null, $status = null)
    {
        if (is_null($status)) {
            $status = $this->successStatus;
        }

        return response()->json(['response' => ['data' => $data, 'message' => $message], 'code' => $status, 'error' => false], $status);
    }

    public function error($message, $data = null, $status = null)
    {
        if (is_null($status)) {
            $status = $this->errorStatus;
        }

        return response()->json(['response' => ['message' => $message, 'data' => $data], 'error' => true, 'code' => $status], $status);
    }

    public function config()
    {
        $data['roles'] = Role::where('active', 1)->where('language', App::getLocale())->get();
        $data['roles_categories'] = RoleCategory::where('active', 1)->where('language',App::getLocale())->get();
        $data['industries'] = Industry::select(['id', 'name'])->where('active', 1)->get();
        $data['generate_questions'] = (int) env('GENERATE_QUESTIONS', 0);
        $data['subjects'] = [
            'Help',
            'Plan'
        ];
//        $data['video_url'] = 'https://reallang.chat/';
        $data['video_url'] = null;
        $data['permissions'] = [
            ['edit_company' => 'Edit Company'],
            ['view_jobs' => 'View Jobs'],
            ['create_jobs' => 'Create Jobs'],
            ['edit_jobs' => 'Edit Jobs'],
            ['rate_responses' => 'Rate Candidates'],
            ['delete_responses' => 'Delete Candidates'],
            ['view_rooms' => 'View rooms'],
            ['create_rooms' => 'Create rooms'],
            ['edit_rooms' => 'Edit rooms'],
            ['view_templates' => 'View templates'],
            ['create_templates' => 'Create templates'],
            ['edit_templates' => 'Edit templates'],
            ['send_template' => 'Send template notification'],
        ];

        $data['email_vars'] = [
                ['title' => 'Interview name', 'value' => '{{interview}}'],
                ['title' => 'Interview link', 'value' => '{{interview_link}}'],
                ['title' => 'Candidate name', 'value' => '{{candidate_name}}'],
                ['title' => 'Company name', 'value' => '{{company_name}}'],
//                ['title' => 'Company phone', 'value' => '{{company_phone}}'],
                ['title' => 'Company website', 'value' => '{{company_website}}'],
                ['title' => 'Interview invite button', 'value' => '{{interview_invite_btn}}'],
                ['title' => 'Response show button', 'value' => '{{response_show_btn}}'],
        ];

        return $this->success($data);
    }
}
