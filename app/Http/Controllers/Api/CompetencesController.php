<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\Response;
use App\Models\Score;
use App\Models\Set;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class CompetencesController extends BaseController
{
    public function sets($lang = 'en')
    {
        if ($lang) {
            if (!in_array($lang, ['ru', 'en', 'es', 'de'])) {
                $lang = App::getLocale();
            }
        }

        $sets = Set::where('active', 1)->where('language', $lang)->get();
        $pipelines = Pipeline::where('language', $lang)->where('default', 1)->get();

        $data['sets'] = $sets;
        $data['pipelines'] = $pipelines;

        return $this->success($data);
    }

    public function createScore(Request $request)
    {
        $user = Auth::user();

        if (!$request->response_id) {
            return $this->error(__('messages.need_response_id'));
        }


        $response = Response::find((int)$request->response_id);

        if (!$response) {
            return $this->error(__('messages.response_not_found'));
        }

        if (!$request->scores || !is_array($request->scores) || !count($request->scores)) {
            return $this->error(__('messages.need_scores'));
        }

        $company = $response->job->company;

        if (!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if (!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.cannot_rate_response'));
        }

        $competences = $response->job->competences;


        foreach ($competences as $competence) {
            $scoreExist = false;
            foreach ($request->scores as $score) {
                $score = json_decode($score);
                if ($score->score_id == $competence->id) {
                    $scoreExist = true;
                    break;
                }
            }

            if (!$scoreExist) {
                return $this->error(__('messages.need_all_scores'));
            }
        }

        foreach ($competences as $competence) {
            foreach ($request->scores as $score) {
                $score = json_decode($score);
                if ($score->score_id == $competence->id) {
                    $newScore = Score::where('user_id', $user->id)->where('competence_id', $competence->id)->where('response_id', $response->id)->first();
                    if(!$newScore) {
                        $newScore = new Score();
                        $newScore->user_id = $user->id;
                        $newScore->response_id = $response->id;
                        $newScore->competence_id = $competence->id;
                    }
                    $newScore->scores = $score->scores;
                    $newScore->compatibility = abs(100 - abs($competence->score - $score->scores));
                    $newScore->save();
                }
            }
        }

        return $this->success(__('messages.response_rated'));
    }
}
