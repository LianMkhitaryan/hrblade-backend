<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Mail\InviteInterwiew;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TemplatesController extends BaseController
{
    public function index($id)
    {
        $user = Auth::user();

        $company = Company::find($id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_templates', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }


        $defaultTemplates = EmailTemplate::where('default', 1)->orderBy('created_at','desc')->get();
        $templates = EmailTemplate::where('company_id', $company->id)->orderBy('created_at','desc')->get();

        if($templates->count()) {
            $res = [];
            foreach ($defaultTemplates as $defaultTemplate) {
                $exist = $templates->where('language', $defaultTemplate->language)->where('type', $defaultTemplate->type)->first();
                if($exist) {
                    $res[$exist->type][$exist->language] = $exist;
                } else {
                    $res[$defaultTemplate->type][$defaultTemplate->language] = $exist;
                }
            }


            return $this->success(collect($res));
        }

        $res = [];
        foreach ($defaultTemplates as $defaultTemplate) {
                $res[$defaultTemplate->type][$defaultTemplate->language] = $defaultTemplate;
        }

        return $this->success(collect($res));
    }

    public function edit(Request $request)
    {
        $user = Auth::user();

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_templates', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        foreach ($request->templates as $template) {
            $template = json_decode($template);
            if(!$template->language) {
                return $this->error( __('messages.need_language'));
            }
            if(isset($template->type) && $template->type) {
                $exist = EmailTemplate::where('language', $template->language)->where('type', $template->type)->where('company_id', $company->id)->first();
                if($exist) {
                    $exist->email = $template->email;
                    $exist->email_title = $template->email_title;
                    $exist->sms = $template->sms;
                    if(isset($template->name) && $template->name) {
                        $exist->name = $template->name;
                    }
                    $exist->save();
                } else {
                    $newTemplate = new EmailTemplate();
                    if( isset($template->type) && $template->type) {
                        $defaultTemplate = EmailTemplate::where('default', 1)->where('language', $template->language)->first();
                        $newTemplate->name = $defaultTemplate->name;
                    }
                    $newTemplate->email = $template->email;
                    if(isset($template->name) && $template->name) {
                        $newTemplate->name = $template->name;
                    }
                    $newTemplate->email_title = $template->email_title;
                    $newTemplate->sms = $template->sms;
                    $newTemplate->company_id = $company->id;
                    $newTemplate->language = $template->language;
                    $newTemplate->type = $template->type;
                    $newTemplate->save();
                }
            } else {
                if(isset($template->id) && $template->id) {
                    $exist = EmailTemplate::find($template->id);
                    if($exist) {
                        $exist->email = $template->email;
                        $exist->email_title = $template->email_title;
                        $exist->sms = $template->sms;
                        if(isset($template->name) && $template->name) {
                            $exist->name = $template->name;
                        }
                        $exist->save();
                    } else {
                        return $this->error(__("messages.template_not_found"));
                    }
                } else {
                    $newTemplate = new EmailTemplate();
                    $newTemplate->email = $template->email;
                    if(isset($template->name) && $template->name) {
                        $newTemplate->name = $template->name;
                    }
                    $newTemplate->email_title = $template->email_title;
                    $newTemplate->sms = $template->sms;
                    $newTemplate->company_id = $company->id;
                    $newTemplate->language = $template->language;
                    $newTemplate->type = $template->type;
                    $newTemplate->save();
                }
            }

        }

        $defaultTemplates = EmailTemplate::where('default', 1)->orderBy('created_at','desc')->get();
        $templates = EmailTemplate::where('company_id', $company->id)->orderBy('created_at','desc')->get();

        if($templates->count()) {
            $res = [];
            foreach ($defaultTemplates as $defaultTemplate) {
                $exist = $templates->where('language', $defaultTemplate->language)->where('type', $defaultTemplate->type)->first();
                if($exist) {
                    $res[$exist->type][$exist->language] = $exist;
                } else {
                    $res[$defaultTemplate->type][$defaultTemplate->language] = $exist;
                }
            }

            return $this->success(collect($res));
        }

        $res = [];
        foreach ($defaultTemplates as $defaultTemplate) {
            $res[$defaultTemplate->type][$defaultTemplate->language] = $defaultTemplate;
        }

        return $this->success(collect($res));
    }

    public function get(Request $request)
    {
        $user = Auth::user();

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_templates', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }


        $language = 'en';

        if ($request->language) {
            if (in_array($request->language, ['ru', 'en', 'es', 'de'])) {
                $language = $request->language;
            }
        }

        App::setLocale($language);

        $view = 'emails.new.invite_interview';

        $content = $request->email;

        $btnInvite = '<a href="" style="background-attachment: scroll;
                    display: block;
                    width: 100%;
                    max-width: 340px;
                    height: 55px;
                    margin-top: 0;
                    margin-bottom: 0;
                    margin-right: auto;
                    margin-left: auto;
                    border-radius: 5px;
                    font-weight: 600;
                    line-height: 55px;
                    font-size: 16px;
                    text-decoration: none;
                    font-family: Helvetica, sans-serif;
                    text-align: center;
                    color: #ffffff;
                    background-color: #ffab42;
                    background-image: none;
                    background-repeat: repeat;
                    background-position: top left;
                    cursor: pointer;
                    box-sizing: border-box;">
                          ' . __('messages.run_interview') . '
                        </a>';

        $content = str_replace('{{interview_invite_btn}}', $btnInvite, $content);

        $sms = $request->sms;
        $preview = 1;

        $data['email'] = view($view, compact('content','company','preview'))->render();
        $data['sms'] = $sms;

        return $this->success($data);
    }

    public function delete(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'template_id' => 'required',
            'company_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_rooms', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $template = EmailTemplate::find((integer) $request->template_id);

        if(!$template->default) {
            $template->delete();
        }

        return $this->success([], __('messages.template_deleted'));
    }

    public function getDefault(Request $request)
    {
        $user = Auth::user();

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_templates', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $type = $request->type;

        $defaultTemplates = EmailTemplate::where('default', 1)->where('type', $type)->orderBy('created_at','desc')->get();

        $res = [];
        foreach ($defaultTemplates as $defaultTemplate) {
            $res[$defaultTemplate->type][$defaultTemplate->language] = $defaultTemplate;
        }

        return $this->success(collect($res));
    }

    public function sendTemplate(Request $request)
    {
        $user = Auth::user();

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('send_template', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $response = Response::where('id', $request->response_id)->first();

        if(!$response) {
            return $this->error(__('messages.company_not_found'));
        }

        $language = $request->language;
        App::setLocale($language);

        $template = EmailTemplate::where('id', $request->template_id)
            ->where(function ($query) use ($company) {
                $query->where('company_id', $company->id)->orWhereNull('company_id');
            })
            ->where('language', $language)->first();

        if(!$template) {
            return $this->error(__('messages.template_not_found'));
        }

        if($response->phone && $request->send_sms) {
            try {
                SmsHelper::send($template->getSmsContent($response, $company), $response->phone);
                $smsSended = 1;
            } catch (\Error $error) {
                $smsSended = 0;
            }
        } else {
            $smsSended = 1;
        }

        try {
            $link = new Link();
            $link->response = $response;
            Mail::to($response->email)->send(new InviteInterwiew($link, $company, $template));
        } catch (\Exception $e) {
            Log::error("Invite email not send to {$response->email}");
        }

        if( $smsSended) {
            return $this->success($response, __('messages.invited'));
        }

        return $this->success($response, __('messages.invited_maybe_no_sms'));
    }
}
