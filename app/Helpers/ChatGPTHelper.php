<?php

namespace App\Helpers;
use Orhanerday\OpenAi\OpenAi;

class ChatGPTHelper
{
    static function analysis($text, $question, $job)
    {
        $open_ai_key = env('CHATGPT_KEY');
        $open_ai = new OpenAi($open_ai_key);
        $answer = $text;

        $content='Rate the candidate`s response at the "'.$job.'" interview from 1 to 10, you answer must be one number, question is "'.$question.'" answer is "'.$answer.'" Your answer must not contain any character other than a number.';

        $complete = $open_ai->chat([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    "role" => "user",
                    "content" => $content
                ],
            ],
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        $arr = json_decode($complete, true);

        $res=$arr["choices"][0]["message"]["content"];

        if (is_numeric($res) && ($res>=0) && ($res<=10)) {
            return $res;
        } else {
           return false;
        }

    }
}
