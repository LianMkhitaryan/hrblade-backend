<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileSaver;
use App\Models\Company;
use App\Models\Competence;
use App\Models\DefaultQuestion;
use App\Models\DefaultQuestionCategory;
use App\Models\Job;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\Question;
use App\Models\Response;
use App\Models\Role;
use App\Models\Test;
use App\Traits\FileUploadS3Trait as FileUploadTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class JobsController extends BaseController
{

    use FileUploadTrait;

    public function jobs()
    {
        $user = Auth::user();

        if($user->isOwner()) {
            $companies = $user->agency->companies;
        } else {
            $permissions = Permission::where('user_id', $user->id)->where('name','view_jobs')->get();
            if(!$permissions->count()) {
                return $this->success([]);
            }
            $companiesIds = $permissions->pluck('company_id')->toArray();
            $companiesIds = array_unique($companiesIds);
            $companies = Company::whereIn('id',$companiesIds)->get();
        }

        if (!$companies) {
            return $this->success([]);
        }

        $jobs = Job::whereIn('company_id', $companies->pluck('id'))->orderBy('created_at','desc')->get();

        return $this->success($jobs);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'sometimes',
            'salary' => 'sometimes',
            'location' => 'sometimes',
            'company_id' => 'required|exists:companies,id',
            'industry_id' => 'sometimes|exists:industries,id',
            'role_id' => 'sometimes|exists:roles,id',
            'for_follow_up' => 'required|boolean',
            'expire_date' => 'sometimes|date',
            'expire_days' => 'sometimes|numeric',
            'start_at' => 'sometimes|date',
            'video' => 'sometimes|URL',
            'header_image' => 'sometimes',
            'ask_cv' => 'sometimes',
            'ask_motivation_letter' => 'sometimes',
            'template' => 'sometimes',
            'random_order' => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $data['agency_id'] =  $user->agency->id;

        $company = Company::find($data['company_id']);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('create_jobs', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        if($company->agency->limits('jobs')) {
            return $this->error(__('messages.jobs_create_limit'));
        }

        $data['start_at'] = Carbon::now();

        if ($request->hasFile('header_image')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('header_image'), 'header_image');
            $data['header_image'] = $savedFile;
        } else if($request->get('header_image') == "clear") {
            $data['header_image'] = null;
        }

        if($request->has('block_try')) {
            $data['block_try'] = $request->get('block_try');
        } else {
            $data['block_try'] = 0;
        }

        if ($request->has('questions')) {
            $videoCount = 0;
            foreach ($request->get('questions') as $question) {
                $question = json_decode($question);
                if (!$question) {
                    return $this->error(__('messages.invalid_json'));
                }
                if($question->type == 'VIDEO') {
                    $videoCount++;
                }
            }
            if($videoCount > $company->agency->limits('questions')) {
                return $this->error(__('messages.questions_limit'));
            }
        }

        $job = Job::create($data);
        $saver = new FileSaver();
        if ($request->has('questions')) {
            foreach ($request->get('questions') as $key => $question) {
                $question = json_decode($question);
                if (!$question) {
                    return $this->error(__('messages.invalid_json'));
                }
                $newQuestion = new Question();
                $newQuestion->type = $question->type;
                $newQuestion->job_id = $job->id;
                $newQuestion->question = $question->question;
                $newQuestion->time = $question->time;
                if (isset($question->is_count)) {
                    $newQuestion->is_count = $question->is_count;
                }
                if (isset($question->points)) {
                    $newQuestion->points = $question->points;
                }
                if (isset($question->sorting)) {
                    $newQuestion->sorting = $question->sorting;
                }
                if (isset($question->copyscape_check)) {
                    $newQuestion->copyscape_check = $question->copyscape_check;
                } else {
                    $newQuestion->copyscape_check = 0;
                }
                if (isset($question->retake)) {
                    $newQuestion->retake = $question->retake;
                }
                if (isset($question->default_id)) {
                    $newQuestion->default_id = $question->default_id;
                }
                if (isset($question->video)) {
                    $newQuestion->video_link = $question->video;
                } else {
                    $newQuestion->video_link = null;
                }
                if (isset($question->preparation_time)) {
                    $newQuestion->preparation_time = $question->preparation_time;
                } else {
                    $newQuestion->preparation_time = null;
                }

                
                if (isset($question->language_answer)) {
                    $newQuestion->language_answer = $question->language_answer;
                } else {
                    $newQuestion->language_answer = null;
                }

                $newQuestion->save();

                if ($request->has("videos")) {
                    if(isset($request->videos[$key])) {
                        $file = $request->videos[$key];
                        $savedVideo = $this->uploadQuestionVideo($file, $newQuestion);
                        $newQuestion->video_transcoded = $savedVideo['transcoded'];
                        $newQuestion->video = $savedVideo['download_link'];
                        $newQuestion->save();
                    }
                }

                if ($request->has("image")) {
                    if(isset($request->image[$key])) {
                        $savedFile = $saver->handle($request->image[$key], 'question');
                        $newQuestion->image = $savedFile;
                        $newQuestion->save();
                    }
                }

                if ($newQuestion->type == 'TEST') {
                    if (isset($question->tests) && is_array($question->tests)) {
                        foreach ($question->tests as $test) {
                            $newTest = new Test();
                            $newTest->question_id = $newQuestion->id;
                            $newTest->correct = $test->correct;
                            $newTest->points = $newQuestion->points;
                            $newTest->text = $test->text;
                            $newTest->is_count = $newQuestion->is_count;
                            if (isset($test->sorting)) {
                                $newTest->sorting = $test->sorting;
                            }
                            $newTest->save();
                        }
                    }
                }
            }
        }

        if ($request->has('competences')) {
            foreach ($request->competences as $competence) {
                $competence = json_decode($competence);
                if(!$competence) {
                    return $this->error(__('messages.invalid_json'));
                }
                $newCompetence = new Competence();
                $newCompetence->name = $competence->name;
                $newCompetence->job_id = $job->id;
                $newCompetence->score = $competence->score;
                $newCompetence->sort = $competence->sort;
                $newCompetence->save();
            }
        }

        if ($request->has('pipelines')) {
            foreach ($request->pipelines as $pipeline) {
                $pipeline = json_decode($pipeline);
                if(!$pipeline) {
                    return $this->error(__('messages.invalid_json'));
                }

                $newPipeline = new Pipeline();
                $newPipeline->job_id = $job->id;
                $newPipeline->name = $pipeline->name;
                $newPipeline->sorting = $pipeline->sort;
                $newPipeline->default = 0;
                $newPipeline->save();
            }
        }

        $job->questions = $job->questions()->with('tests')->get();
        $link = new Link();
        $link->job_id = $job->id;
        $link->hash = Str::random(26);
        while (Link::where('hash', $link->hash)->first()) {
            $link->hash = Str::random(26);
        }
        $link->active = 1;
        $link->save();

        return $this->success($job);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'sometimes',
            'salary' => 'sometimes',
            'company_id' => 'required|exists:companies,id',
            'industry_id' => 'sometimes|exists:industries,id',
            'role_id' => 'sometimes|exists:roles,id',
            'for_follow_up' => 'required|boolean',
            'expire_date' => 'sometimes|date',
            'expire_days' => 'sometimes|numeric',
            'start_at' => 'sometimes|date',
            'video' => 'sometimes|URL',
            'location' => 'sometimes',
            'ask_cv' => 'sometimes',
            'ask_motivation_letter' => 'sometimes',
            'job_id' => 'required|exists:jobs,id',
            'header_image' => 'sometimes',
            'template' => 'sometimes',
            'random_order' => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $company = Company::find($data['company_id']);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_jobs', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }


        $job = Job::find($data['job_id']);

        if($job->agency_id != $user->agency->id) {
            return $this->error(__('messages.job_not_found'));
        }

        if ($request->hasFile('header_image')) {
            $saver = new FileSaver();
            $savedFile = $saver->saveFile($request->file('header_image'), 'header_image');
            $data['header_image'] = $savedFile;
        }  else if($request->get('header_image') == "clear") {
            $data['header_image'] = null;
        }

        if ($request->has('questions')) {
            $videoCount = 0;
            foreach ($request->get('questions') as $question) {
                $question = json_decode($question);
                if (!$question) {
                    return $this->error(__('messages.invalid_json'));
                }
                if($question->type == 'VIDEO') {
                    $videoCount++;
                }
            }
            if($videoCount > $company->agency->limits('questions')) {
                return $this->error(__('messages.questions_limit'));
            }
        }

        if($request->has('block_try')) {
            $data['block_try'] = $request->get('block_try');
        }

        $job->update($data);

        $existQuestionsIds = [];
        $saver = new FileSaver();
        if ($request->has('questions')) {
            foreach ($request->get('questions') as $key => $question) {
                $question = json_decode($question);
                if (!$question) {
                    return $this->error(__('messages.invalid_json'));
                }
                if (isset($question->question_id) && (int)$question->question_id > 0) {
                    $newQuestion = Question::find($question->question_id);

                    if (!$newQuestion || $newQuestion->job_id != $job->id) {
                        continue;
                    }
                } else {
                    $newQuestion = new Question();
                }
                $newQuestion->type = $question->type;
                $newQuestion->job_id = $job->id;
                $newQuestion->question = $question->question;
                $newQuestion->time = $question->time;
                if (isset($question->is_count)) {
                    $newQuestion->is_count = $question->is_count;
                }
                if (isset($question->copyscape_check)) {
                    $newQuestion->copyscape_check = $question->copyscape_check;
                } else {
                    $newQuestion->copyscape_check = 0;
                }
                if (isset($question->video)) {
                    $newQuestion->video_link = $question->video;
                } else {
                    $newQuestion->video_link = null;
                }
                if (isset($question->points)) {
                    $newQuestion->points = $question->points;
                }
                if (isset($question->sorting)) {
                    $newQuestion->sorting = $question->sorting;
                }
                if (isset($question->retake)) {
                    $newQuestion->retake = $question->retake;
                }
                if (isset($question->default_id)) {
                    $newQuestion->default_id = $question->default_id;
                }
                if (isset($question->preparation_time)) {
                    $newQuestion->preparation_time = $question->preparation_time;
                }

               
                if (isset($question->language_answer)) {
                    $newQuestion->language_answer = $question->language_answer;
               }

                
                $newQuestion->save();

                if ($request->has("videos")) {
                    if(isset($request->videos[$key])) {
                        if($request->videos[$key] == "clear") {
                            $newQuestion->video = null;
                            $newQuestion->save();
                        } else {
                            $file = $request->videos[$key];
                            $savedVideo = $this->uploadQuestionVideo($file, $newQuestion);
                            $newQuestion->video_transcoded = $savedVideo['transcoded'];
                            $newQuestion->video = $savedVideo['download_link'];
                            $newQuestion->save();
                        }

                    }
                }

                if ($request->has("image")) {
                    if(isset($request->image[$key])) {
                        if($request->image[$key] == "clear") {
                            $newQuestion->image  = null;
                            $newQuestion->save();
                        } else {
                            $savedFile = $saver->handle($request->image[$key], 'question');
                            $newQuestion->image = $savedFile;
                            $newQuestion->save();
                        }
                    }
                }

                if ($newQuestion->type == 'TEST') {
                    if (isset($question->tests) && is_array($question->tests)) {
                        foreach ($question->tests as $test) {
                            if (isset($test->test_id) && (int)$test->test_id > 0) {
                                $newTest = Test::find($test->test_id);

                                if (!$newTest || $newQuestion->id != $newTest->id) {
                                    continue;
                                }
                            } else {
                                $newTest = new Test();
                            }
                            $newTest->question_id = $newQuestion->id;
                            $newTest->correct = $test->correct;
                            $newTest->points = $newQuestion->points;
                            $newTest->text = $test->text;
                            $newTest->is_count = $newQuestion->is_count;
                            if (isset($test->sorting)) {
                                $newTest->sorting = $test->sorting;
                            }
                            $newTest->save();
                        }
                    }
                }
                $existQuestionsIds[] = $newQuestion->id;
            }
        }

        $job->questions()->whereNotIn('id', $existQuestionsIds)->delete();

        $existCompetencesIds = [];

        if ($request->has('competences')) {
            foreach ($request->competences as $competence) {
                $competence = json_decode($competence);
                if(!$competence) {
                    return $this->error(__('messages.invalid_json'));
                }
                if(isset($competence->competence_id) && (int) $competence->competence_id > 0) {
                    $newCompetence = Question::find($competence->competence_id);

                    if(!$newCompetence || $newCompetence->job_id != $job->id) {
                        continue;
                    }
                } else {
                    $newCompetence = new Competence();
                    $newCompetence->job_id = $job->id;
                }
                $newCompetence->name = $competence->name;
                $newCompetence->score = $competence->score;
                $newCompetence->sort = $competence->sort;
                $newCompetence->save();

                $existCompetencesIds[] = $newCompetence->id;
            }
        }
        $existPipelinesIds = [];
        if ($request->has('pipelines')) {
            foreach ($request->pipelines as $pipeline) {
                $pipeline = json_decode($pipeline);
                if(!$pipeline) {
                    return $this->error(__('messages.invalid_json'));
                }
                if(isset($pipeline->pipelines_id) && (int) $pipeline->pipelines_id > 0) {
                    $newPipeline = Pipeline::find($pipeline->pipelines_id);
                    if(!$newPipeline || $newPipeline->job_id != $job->id) {
                        continue;
                    }
                } else {
                    $newPipeline = new Pipeline();
                    $newPipeline->job_id = $job->id;
                }
                $newPipeline->name = $pipeline->name;
                $newPipeline->sorting = $pipeline->sort;
                $newPipeline->default = 0;
                $newPipeline->save();

                $existPipelinesIds[] = $newPipeline->id;
            }
        }

        $job->pipelines()->whereNotIn('id', $existPipelinesIds)->delete();
        $job->competences()->whereNotIn('id', $existCompetencesIds)->delete();
        $job->questions = $job->questions()->with('tests')->get();
        $job->competences = $job->competences()->get();
        $job->pipelines = $job->pipelines()->get();

        return $this->success($job);
    }

    public function active(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'active' => 'required|boolean',
            'job_id' => 'required|exists:jobs,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $job = Job::find($data['job_id']);

        if(!$job) {
            return $this->error(__('messages.job_not_found'));
        }

        $company = Company::find($job->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_jobs', $company->id)) {
            return $this->error(__('messages.job_not_found'));
        }

        $job->active = $data['active'];

        $job->save();

        return $this->success($job, __('messages.job_active_changed'));
    }

    public function questions($id)
    {
        $role = Role::find($id);

        if(!$role || !$role->active) {
            return $this->error(__('messages.role_not_found'));
        }

        return $this->success($role->questions()->with('category')->get());
    }

    public function defaultQuestionsCategories()
    {
        $locale = App::getLocale();

        $defaultQuestionCategories = DefaultQuestionCategory::where('language', $locale)->get();

        return $this->success($defaultQuestionCategories);
    }

    public function defaultQuestionsByCategory($id)
    {
        $defaultQuestionCategory = DefaultQuestionCategory::find($id);

        if(!$defaultQuestionCategory) {
            return $this->error(__('messages.default_category_found'));
        }

        return $this->success(DefaultQuestion::where('category_id', $defaultQuestionCategory->id)->get());
    }

    public function job($id)
    {
        $user = Auth::user();

        $job = Job::with(['company:id,name', 'questions.tests', 'competences', 'pipelines', 'responses' => function ($query) {
            $query->select(['id', 'job_id', 'status', 'pipeline_id', 'default_cv', 'pipeline_index', 'phone', 'email', 'visited_at', 'completed_at', 'created_at', 'full', 'rating'])->with(['answers' => function ($query) {
                $query->select(['id', 'response_id', 'rate'])->withCount('copyscapes');
            }, 'pipeline:id,name']);
        }])->find($id);

        $company = Company::find($job->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('view_jobs', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        foreach ($job->questions as $question) {
            if($question->type == "TEST") {
                foreach ($question->tests as $test) {
                    $test->makeVisible('correct');
                }
            }
        }

        foreach ($job->responses as $response) {
            $answer = $response->answers()->whereNotNull('video_thumb')->first();
            if($answer) {
                $response->preview_image = $answer->video_thumb;
            } else {
                $response->preview_image = null;
            }
        }

        return $this->success($job);
    }

    public function remove($id)
    {
        $user = Auth::user();

        $job = Job::with('responses')->find($id);

        if(!$user->isOwner()) {
            return $this->error(__('messages.not_have_permissions'));
        }

        if(!$job || $job->company->agency_id != $user->agency->id) {
            return $this->error(__('messages.job_not_found'));
        }

        foreach ($job->responses as $response) {
            $response->answers()->delete();
            Storage::disk(env('FILESYSTEM_DRIVER'))->deleteDirectory('responses/' . $response->id);
        }

        $job->responses()->delete();
        $job->questions()->delete();
        $job->delete();

        return $this->success(__('messages.job_deleted'));
    }

    public function exportVideos($id)
    {
        $user = Auth::user();

        if($id) {
            $job = Job::with('responses.answers', 'company')->find($id);
        } else {
            $job = Job::with('responses.answers', 'company')->where($user->company_id)->get();
        }


        if(!$user->perm('view_jobs', $job->company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $videos = [];

        foreach ($job->responses as $response) {
            $videos[$response->id]['name'] = $response->full;
            $videos[$response->id]['email'] = $response->email;
            foreach ($response->answers as $answer) {
                $videos[$response->id][] = $answer->video;
            }
        }

        return $this->success($videos, 'Ok');
    }


    public function export($id, $token)
    {
      //  $user = Auth::user();

        $job = Job::find($id);
//
//        if(!$job || $job->company->agency_id != $user->agency->id) {
//            return $this->error('Job not found');
//        }


        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$job->id}_" . gmdate("d_M_Y") . '.csv',
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        $pipelines = $job->pipelines;
        $responses = Response::where('job_id', $job->id)->get();
        $columns = ['id','status','name','email','phone','location', 'rating','note','comment', 'pipeline'];
        $callback = function() use($responses, $columns, $pipelines) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($responses as $response) {
                fputcsv($file,
                    [
                        $response->id,
                        $response->status,
                        $response->full,
                        $response->email,
                        $response->phone,
                        $response->location,
                        $response->rating,
                        $response->note,
                        $response->comment,
                        $pipelines->where('id', $response->pipeline_id)->first() ? $pipelines->where('id', $response->pipeline_id)->first()->name : ''
                    ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

        $df = fopen("php://output", 'w');
        $responses = Response::where('job_id', $job->id)->get();
        fputcsv($df, ['id','status','name','email','phone','location', 'rating','note','comment','pipeline']);
        foreach ($responses as $response) {
            fputcsv($df,
                [
                    $response->id,
                    $response->status,
                    $response->full,
                    $response->email,
                    $response->phone,
                    $response->location,
                    $response->rating,
                    $response->note,
                    $response->comment,
                    $pipelines->where('id', $response->pipeline_id)->first() ? $pipelines->where('id', $response->pipeline_id)->first()->name : ''
                ]);
        }
        fclose($df);


        die();
    }
}
