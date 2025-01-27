<?php

namespace App\Http\Controllers\Api;

use App\Models\Answer;
use App\Models\CopyscapeUrl;
use App\Models\Response;
use Illuminate\Support\Facades\Log;


class CopyscapeConntroller extends BaseController
{
    public function checkResponse(Response $response)
    {
        $answers = $response->answers;

        foreach ($answers as $answer) {
            $this->checkAnswer($answer);
        }
    }

    public function checkAnswer(Answer $answer)
    {
        if (is_null($answer->text) && mb_strlen($answer->text)) {
            return;
        }

        if(!$answer->question->copyscape_check) {
            return;
        }

        $this->copyscapeApiTextSearch($answer, "UTF-8", null, 'csearch');
    }

    private function copyscapeApiCall($operation, $params = array(), $answer = null)
    {
        $url = env('COPYSCAPE_BASE') . '?u=' . urlencode(env('COPYSCAPE_USER')) .
            '&k=' . urlencode(env('COPYSCAPE_KEY')) . '&o=' . urlencode($operation);

        foreach ($params as $name => $value)
            $url .= '&' . urlencode($name) . '=' . urlencode($value);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $answer->text);

        $response = curl_exec($curl);
        curl_close($curl);
        Log::info(var_export($response, true));
        $ourResponse = $answer->response;

        if(!$ourResponse) {
            return;
        }

        if (strlen($response)) {
            $response = json_decode($response);
            if(isset($response->querywords)) {
                $wordsCount = $response->querywords;
                if(isset($response->count) && $response->count > 0) {
                    foreach ($response->result as $res) {
                        $copyscapeUrl = new CopyscapeUrl();
                        $copyscapeUrl->answer_id = $answer->id;
                        $copyscapeUrl->url = $res->url;
                        $copyscapeUrl->compare_url = $res->viewurl;
                        $copyscapeUrl->words_matches = $res->minwordsmatched;
                        $copyscapeUrl->text_snippet = $res->textsnippet;
                        $copyscapeUrl->agency_id = $ourResponse->agency_id;
                        $copyscapeUrl->percent = round($res->minwordsmatched / $wordsCount * 100);
                        $copyscapeUrl->save();
                    }
                }
            }
        }
    }

    private function copyscapeApiTextSearch($answer, $encoding, $full = null, $operation = 'csearch')
    {
        $params['e'] = $encoding;
        $params['f'] = 'json';

        if (isset($full))
            $params['c'] = $full;

        $this->copyscapeApiCall($operation, $params, $answer);
    }
}
