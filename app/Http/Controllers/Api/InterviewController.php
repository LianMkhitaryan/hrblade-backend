<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InviteInterwiew;
use App\Models\Job;
use App\Models\Link;
use App\Models\Response;
use App\Models\TestAnswer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InterviewController extends BaseController
{
    public function page(Request $request, $hash)
    {
        $link = Link::where('hash', $hash)->first();

        if(!$link || !$link->active) {
            return $this->error(__('messages.not_found'));
        }

        $job = Job::with(['company', 'questions.tests'])->find($link->job_id);


        if(!$job || !$job->active) {
            return $this->error(__('messages.not_found'));
        }

        if($job->expire_date) {
            if(Carbon::now() > $job->expire_date) {
                return $this->error(__('messages.job_expired'));
            }
        }

        if($job->expire_days) {
            if(Carbon::now() > Carbon::parse($job->start_at)->addDays($job->expire_days)) {
                return $this->error(__('messages.job_expired'));
            }
        }

//        $exist = Response::where('job_id', $job->id)->whereNotNull('ip')->where('ip', $request->getClientIp())->first();
//
//        if($exist) {
//            return $this->error('Resume with IP already exist');
//        }


        $job->limit = $job->company->agency->limits('responses');

        $data['job'] = $job;
        $data['response'] = null;
        $data['blocked'] = 0;

        if($link->response_id) {
            $response = Response::find($link->response_id);
            if($response) {
                $data['response'] = $response;
                $data['blocked'] = $response->blocked;
            }

        }

        if($request->random_now) {
            if($job->random_order) {
                if($job->questions) {
                    $questions = $job->questions->shuffle();
                    unset($job->questions);
                    $job->questions = $questions;
                }
            }
        }

        return $this->success($data);
    }

    public function response($hash)
    {
        $response = Response::with('answers.question.tests')->where('hash', $hash)->first();

        if(!$response) {
            return $this->error(__('messages.not_found'));
        }

        foreach ($response->answers as $answer) {
            if($answer->question) {
                if($answer->question->type == "TEST") {
                    $answer->answer = TestAnswer::where('response_id', $response->id)->where('question_id', $answer->question->id)->get();
                    foreach ($answer->question->tests as $test) {
                        $test->answer = $test->answers()->where('response_id', $response->id)->first();
                        $test->makeVisible('correct');
                    }
                }
            }
        }

        return $this->success($response);
    }

    public function block(Request $request, $hash)
    {
        $link = Link::where('hash', $hash)->first();

        if(!$link) {
            return $this->error(__('messages.not_found'));
        }

        if(!$link->response_id) {
            return $this->error(__('messages.not_found'));
        }

        $response = Response::find($link->response_id);

        if(!$response) {
            return $this->error(__('messages.not_found'));
        }

        $response->blocked = $request->block > 0 ? 1 : 0;
        $response->save();

        return $this->success([], 'Blocked');
    }

}
