<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AzureHelper;
use App\Helpers\ChatGPTHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileSaver;
use App\Mail\InviteInterwiew;
use App\Models\Answer;
use App\Models\EmailTemplate;
use App\Models\Hook;
use App\Models\Job;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\Response;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use App\Traits\FileUploadS3Trait as FileUploadTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResponsesController extends BaseController
{

    use FileUploadTrait;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hash' => 'required',
            'question_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $link = Link::where('hash', $data['hash'])->first();

        if (!$link || !$link->active) {
            return $this->error(__('messages.not_found'));
        }

        $job = Job::with(['company', 'questions'])->find($link->job_id);


        if($job->company->agency->limits('responses')) {
            return $this->error(__('messages.responses_limit'));
        }

        if (!$job || !$job->active) {
            return $this->error(__('messages.not_found'));
        }

        if ($job->expire_date) {
            if (Carbon::now() > $job->expire_date) {
                return $this->error(__('messages.job_expired'));
            }
        }

        if ($job->expire_days) {
            if (Carbon::now() > Carbon::parse($job->start_at)->addDays($job->expire_days)) {
                return $this->error(__('messages.job_expired'));
            }
        }


//        foreach ($job->questions as $key => $question) {
//            if (!in_array($question->id, $request->question_id)) {
//                return $this->error(__('messages.need_all_answers'));
//            }
//            if ($question->type == "VIDEO") {
//                if (!isset($request->video[$key]) || !is_file($request->video[$key])) {
//                    return $this->error(__('messages.need_all_answers'));
//                }
//            }
//        }

//        if ($job->ask_cv) {
//            if (!$request->hasFile('ask_cv')) {
//                return $this->error(__('messages.need_cv'));
//            }
//        }
//
//        if ($job->ask_motivation_letter) {
//            if (!$request->hasFile('ask_motivation_letter')) {
//                return $this->error(__('messages.need_motivation'));
//            }
//        }


        if ($link->response_id) {
            $response = Response::find($link->response_id);
            if (!$response) {
                return $this->error(__('messages.error'));
            }
            $response->status = 'REVIEW';
            $response->save();
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }

            $exist = Response::where('job_id', $job->id)->where('email', $request->email)->first();

            if ($exist) {
                return $this->error(__('messages.resume_email_exist'));
            }

//            $exist = Response::where('job_id', $job->id)->whereNotNull('ip')->where('ip', $request->getClientIp())->first();
//
//            if($exist) {
//                return $this->error('Resume with IP already exist');
//            }

            $data['job_id'] = $job->id;
            $data['company_id'] = $job->company->id;
            $data['status'] = 'REVIEW';
            $data['full'] = $data['name'];
            $data['agency_id'] = $job->agency_id;

            unset($data['name']);
            unset($data['hash']);

            $data['hash'] = Str::random(26);

            while (Response::where('hash', $data['hash'])->first()) {
                $data['hash'] = Str::random(26);
            }

            $response = Response::create($data);
        }

        $response->ip = $request->getClientIp();
        $response->save();


        $ai_array=array();

        $test_er=0;
        $test_ok=0;
        $test_points=0;

        foreach ($job->questions as $question) {
            $answer = new Answer();
            $answer->response_id = $response->id;
            $answer->question_id = $question->id;
            $key = array_search($question->id, $request->question_id);
            if ($question->type == "VIDEO") {
                if($request->video[$key]) {
                    try {
                        $file = $request->video[$key];
                        $savedVideo = $this->uploadAnswerVideo($file, $response, $question);
                        if($savedVideo) {
                            $answer->video = $savedVideo['download_link'];
                            $answer->video_thumb = $savedVideo['thumb'];
                            $answer->video_transcoded = $savedVideo['transcoded'];
                            $answer->video_gif = $savedVideo['gif'];
                            $answer->video_time = $savedVideo['time'];
                        }
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage());
                    }
                }
            }

            $test_error=0;

            if ($question->type == "TEST") {
                foreach (json_decode($request->tests[$key]) as $testAnswer) {
                    if (!$testAnswer) {
                        return $this->error(__('messages.invalid_json'));
                    }
                    $test = Test::find($testAnswer->test_id);

                    $userAnswer = new TestAnswer();

                    $userAnswer->correct = $testAnswer->correct;

                    if ($testAnswer->correct != $test->correct) { 
                        $test_error=1; 
                    };

                    $userAnswer->response_id = $response->id;
                    $userAnswer->answer = $testAnswer->text;
                    $userAnswer->question_id = $question->id;
                    $userAnswer->test_id = $test->id;
                    $userAnswer->save();
                }
            }

            if ($test_error==1) {
                $test_er=$test_er+1;
            } else {
                $test_ok=$test_ok+1;
                if ($question->is_count == 1) {
                  $test_points=$test_points+$question->points;  
                } 
            };

            $aai='';
            
            if (isset($request->answer[$key])) {
                $aai = $request->answer[$key];
            } else {
                if (isset($request->text[$key])) {
                    $aai = $request->text[$key];
                }
            }
                
            

            if (isset($aai) && $aai!='') {
                $answer->text = $aai;
                $analysis_a = AzureHelper::analysis($aai);

                if ($analysis_a) {
                    if (isset($analysis_a['emotions'])) {
                        $answer->positive = $analysis_a['emotions']['positive'];
                        $answer->negative = $analysis_a['emotions']['negative'];
                        $answer->neutral = $analysis_a['emotions']['neutral'];
                    }

                    if (isset($analysis_a['keywords'])) {
                            $answer->keywords = json_encode($analysis_a['keywords']);
                    }
                }

                $analysis = ChatGPTHelper::analysis($aai, $question->en, $job->name);
                
                
                if ($analysis) {
                    $answer->ii_score = $analysis;
                    $ai_array[]=$analysis;
                  //$answer->ai_analysis = $analysis;
                }
                
            }

            if (isset($request->answer[$key])) {
                $answer->answer = $request->answer[$key];
                if (!isset($request->text[$key])) {
                    $answer->text = $request->answer[$key];
                }
            }

            $answer->save();
        }

        if ( $test_ok>0 || $test_er>0 ) {
            $response->note = 'TEST: [+] '.$test_ok.' [-] '.$test_er.' [=] '.$test_points;
        };

        if (count($ai_array)>=1) {
            $ai_average_all = array_sum($ai_array)/count($ai_array)/2;
            $ai_average = round($ai_average_all,0);
            $response->rating = $ai_average;
        }

        if ($job->ask_cv) {
            if($request->hasFile('ask_cv')) {
                $name = md5($request->file('ask_cv')->getClientOriginalName()) . '_' . rand(10, 10000);
                $extension = $request->file('ask_cv')->extension();
                $path = "responses/{$response->id}/CV/$name.$extension";
                Storage::disk(env('FILESYSTEM_DRIVER'))->put($path, file_get_contents($request->file('ask_cv')));
                Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($path, 'public');
                $response->ask_cv = $path;
            }
        }

        if ($job->ask_motivation_letter) {
            if($request->hasFile('ask_motivation_letter')) {
                $name = md5($request->file('ask_motivation_letter')->getClientOriginalName()) . '_' . rand(10, 10000);
                $extension = $request->file('ask_motivation_letter')->extension();
                $path = "responses/{$response->id}/ML/$name.$extension";
                Storage::disk(env('FILESYSTEM_DRIVER'))->put($path, file_get_contents($request->file('ask_motivation_letter')));
                Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($path, 'public');
                $response->ask_motivation_letter = $path;
            }
        }

        $pipelines = $job->pipelines;

        if($job->pipelines->count() && is_null($response->pipeline)) {
            $response->pipeline_id = $pipelines->first()->id;
        }

        $response->save();

        Storage::disk('public')->deleteDirectory("/responses/{$response->id}");

        $permissionsUsers = Permission::where('company_id', $job->company_id)->get();

        $usersHookIds = [];

        if ($permissionsUsers->count()) {
            $usersHookIds = $permissionsUsers->pluck('user_id')->toArray();
            $usersHookIds = array_unique($usersHookIds);
        }

        $companyOwner = User::where('role', 'OWNER')->where('agency_id', $job->company->agency_id)->first();

        if ($companyOwner) {
            if (!in_array($companyOwner->id, $usersHookIds)) {
                $usersHookIds[] = $companyOwner->id;
            }
        }

        $hooks = Hook::whereIn('user_id', $usersHookIds)->where('event_id', 'new_response')->get();

        if ($hooks->count()) {
            $zapier['id'] = $response->id;
            $zapier['name'] = $response->full;
            $zapier['phone'] = $response->phone;
            $zapier['email'] = $response->email;
            $zapier['location'] = $response->location;
            $zapier['created_at'] = $response->created_at->format('Y-m-d\TH:i\Z');
            $zapier['company_name'] = $response->job->company->name;
            $zapier['job_name'] = $response->job->name;
            $jsonEncodedData = json_encode($zapier);
            $curl = curl_init();

            foreach ($hooks as $hook) {
                $opts = array(
                    CURLOPT_URL => $hook->hook_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => $jsonEncodedData,
                    CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Content-Length: ' . strlen($jsonEncodedData))
                );
                curl_setopt_array($curl, $opts);
                curl_exec($curl);
            }
        }

        if ($job->company && $job->company->agency && $job->company->agency->isEnterprise() && !$job->company->agency->limits('copyscape')) {
            $cs = new CopyscapeConntroller();
            $cs->checkResponse($response);
        }

        return $this->success($response, __('messages.response_created'));
    }

    public function videoRate(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'rate' => 'required',
            'answer_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $answer = Answer::find($data['answer_id']);

        $company = $answer->question->job->company;

        if (!$company) {
            return $this->error( __('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $answer->rate = $data['rate'];
        $answer->save();

        return $this->success($answer, __('messages.video_rated'));
    }

    public function note(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'note' => 'required',
            'response_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $response = Response::find($data['response_id']);

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $response->note = $data['note'];
        $response->save();

        return $this->success($response, __('messages.response_noted'));
    }

    public function rating(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:0,5',
            'response_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $response = Response::find($data['response_id']);

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $response->rating = $data['rating'];
        $response->save();

        return $this->success($response, __('messages.response_rated'));
    }

    public function comment(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'comment' => 'required',
            'response_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $response = Response::find($data['response_id']);

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $response->note = $data['comment'];
        $response->save();

        return $this->success($response, __('messages.response_commented'));
    }

    public function get($id)
    {
        $user = Auth::user();

        $response = Response::with(['answers.question.tests','answers.copyscapes', 'job','pipeline', 'comments.user:id,name,profile_photo_path', 'scores.user:id,name,profile_photo_path'])->find($id);



        foreach ($response->answers as $answer) {
            $answer->video;
            if ($answer->question) {
                if ($answer->question->type == "TEST") {
                    $answer->answer = TestAnswer::where('response_id', $response->id)->where('question_id', $answer->question->id)->get();
                    foreach ($answer->question->tests as $test) {
                        $test->answer = $test->answers()->where('response_id', $response->id)->first();
                        $test->makeVisible('correct');
                    }
                }
            }
            $answer->copyscapes_count = $answer->copyscapes()->count();
            $answer->copyscape_percent = $answer->copyscapes()->max('percent');
        }

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $companyOwner = 0;

        if ($user->isOwner() && $user->agency_id == $company->agency_id) {
            $companyOwner = 1;
        }

        foreach ($response->comments as $comment) {
            $comment->can_remove = false;
            if($companyOwner) {
                $comment->can_remove = true;
            } else {
                if ($user->id == $comment->user_id) {
                    $comment->can_remove = true;
                }
            }
        }

        $response->competences = $response->job->competences;

        $response->overall_compatibility = 0;

        if($response->scores->count()) {
            foreach ($response->scores as $score) {
                $response->overall_compatibility += $score->compatibility;
            }
            $response->overall_compatibility = (int) ($response->overall_compatibility / $response->scores->count());
        }

        $answers = $response->answers->sortBy(function ($answer) {
            return (int) $answer->question->sorting;
        });

        foreach ($answers as $key => $answer) {
            $answer->order = $key;
        }

        $response->answers = array_values($answers->toArray());

        return $this->success($response);
    }

    public function status(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'response_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $response = Response::find($data['response_id']);

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        if($response->status == 'INVITED') {
            return $this->error(__('messages.status_invited'));
        }

        $response->status = $data['status'];
        $response->save();

        return $this->success($response, __('messages.response_status_changed'));
    }

    public function sendInvite($id)
    {
        $user = Auth::user();

        $response = Response::find($id);

        if(!$response) {
            return $this->error(__('messages.response_not_found'));
        }

        $job = $response->job;

        if (!$job || !$job->active) {
            return $this->error(__('messages.job_not_found'));
        }

        if(!$user->agency->companies->count() || !in_array($job->company_id, $user->agency->companies->pluck('id')->toArray())) {
            return $this->error(__('messages.job_not_found'));
        }

        if ($job->expire_date) {
            if (Carbon::now() > $job->expire_date) {
                return $this->error(__('messages.job_not_found'));
            }
        }

        if ($job->expire_days) {
            if (Carbon::now() > Carbon::parse($job->start_at)->addDays($job->expire_days)) {
                return $this->error(__('messages.job_expired'));
            }
        }

        if($response->status != 'NEW') {
            return $this->error(__('messages.invite_already_sent'));
        }

        $company = $job->company;

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        $link = new Link();
        $link->job_id = $job->id;
        $link->hash = Str::random(26);
        while (Link::where('hash', $link->hash)->first()) {
            $link->hash = Str::random(26);
        }
        $link->active = 1;
        $link->response_id = $response->id;
        $link->save();

        $language = $response->language;
        App::setLocale($language);

        $template = EmailTemplate::where('type', 'INVITE')->where('company_id', $job->company_id)->where('language', $language)->first();

        if(!$template) {
            $template = EmailTemplate::where('type', 'INVITE')->where('default', 1)->where('language', $language)->first();
        }

        if(isset($data['phone'])) {
            try {
                SmsHelper::send($template->getSmsContent($response, $company), $data['phone']);
                $smsSended = 1;
            } catch (\Error $error) {
                $smsSended = 0;
            }
        } else {
            $smsSended = 1;
        }

        try {
            Mail::to($response->email)->send(new InviteInterwiew($link,$company, $template));
        } catch (\Exception $e) {
            Log::error("Invite email not send to {$response->email}");
        }


        $response->status = 'INVITED';
        $response->save();

        if( $smsSended) {
            return $this->success($response, __('messages.invited'));
        }

        return $this->success($response, __('messages.invited_maybe_no_sms'));
    }

    public function changePipeline(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'pipeline_id' => 'required',
            'pipeline_index' => 'sometimes',
            'response_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $response = Response::find($data['response_id']);

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        $pipeline = Pipeline::find($data['pipeline_id']);

        if(!$pipeline || $pipeline->job_id != $response->job_id) {
            return $this->error(__('messages.pipeline_not_found'));
        }

        $pipelineIndex = (int) $request->get('pipeline_index');

        if($response->pipeline_id == $pipeline->id) {
            $allResponses = Response::where('pipeline_id', $response->pipeline_id)
                ->where('id', '!=',$response->id)
                ->orderBy('pipeline_index', 'asc')
                ->get();

            if($allResponses->count()) {
                $count = $allResponses->count();
                for ($i = 0; $i <= $count; $i++) {
                    if($pipelineIndex == $i) {
                        $response->pipeline_index = $i;
                        $response->save();
                        $i++;
                    }

                    $res = $allResponses->shift();
                    if($res) {
                        $res->pipeline_index = $i;
                        $res->save();
                    }
                }
            } else {
                $response->pipeline_index = $pipelineIndex;
                $response->save();
            }
        } else {
            $allOldResponses = Response::where('pipeline_id', $response->pipeline_id)
                ->where('id', '!=',$response->id)
                ->orderBy('pipeline_index', 'asc')
                ->get();

            if($allOldResponses->count()) {
                $count = $allOldResponses->count();
                for ($i = 0; $i <= $count; $i++) {
                    $res = $allOldResponses->shift();
                    if($res) {
                        $res->pipeline_index = $i;
                        $res->save();
                    }
                }
            }


            $allNewResponses = Response::where('pipeline_id', $pipeline->id)
                ->orderBy('pipeline_index', 'asc')
                ->get();

            if($allNewResponses->count()) {
                $count = $allNewResponses->count() + 1;
                for ( $i = 0; $i <= $count; $i++) {
                    if($pipelineIndex == $i) {
                        $response->pipeline_index = $i;
                        $response->pipeline_id = $pipeline->id;
                        $response->save();
                        $i++;
                    }

                    $res = $allNewResponses->shift();
                    if($res) {
                        $res->pipeline_index = $i;
                        $res->save();
                    }
                }
            } else {
                $response->pipeline_index = $pipelineIndex;
                $response->pipeline_id = $pipeline->id;
                $response->save();
            }
        }


        $response->pipeline;

        return $this->success([], __('messages.pipeline_changed'));
    }

    private function countAnswerResult($answer) {
        $question = $answer->question;
        if (!$question || !$question->defaultQuestion) {
            return;
        }
        if ($answer->text == null) {
            return;
        }
        $startedScore = 7;
        $text = $answer->text;
        if (!is_null($answer->positive)) {
            $pointsFromEmotions = 0;

            $pointsFromEmotions += $this->getPointsFromEmotion($question->defaultQuestion->positive, $answer->positive * 10);
            $pointsFromEmotions += $this->getPointsFromEmotion($question->defaultQuestion->negative, $answer->negative * 10);
            $pointsFromEmotions += $this->getPointsFromEmotion($question->defaultQuestion->neutral, $answer->neutral * 10);

            $startedScore += $pointsFromEmotions / 100;
        }

        $wordsScores = 0;

        foreach ($question->defaultQuestion->keywords as $defaultWord => $score) {
            $countWords = mb_substr_count($text, trim($defaultWord));
            if ($countWords > 0) {
                $wordsScores += $countWords * $score;
                break;
            }
        }

        $startedScore += $wordsScores / 100 * 1.5;

        if ($startedScore < 10 && $startedScore > 0) {
            $answer->ii_score = $startedScore;
        } else {
            $answer->ii_score = $startedScore > 10 ? 10 : 0;
        }
    }

    private function getPointsFromEmotion($default, $real)
    {
        $delta = $default - $real;
        if (abs($delta) > 30) {
            return 0 - abs($delta);
        } else {
            return 0 + abs($delta);
        }
    }

    public function remove($id) {
        $user = Auth::user();

        $response = Response::find($id);

        if(!$response) {
            return $this->error("Response not found");
        }

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('delete_responses', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $response->answers()->delete();
        $response->comments()->delete();
        $response->scores()->delete();
        $response->link()->delete();
        $response->delete();

        return $this->success(__('messages.response_deleted'));
    }
}
