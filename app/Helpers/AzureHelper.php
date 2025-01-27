<?php

namespace App\Helpers;

class AzureHelper
{
    static function analysis($text)
    {
        $curl = curl_init();

        $key = env('AZURE_KEY');

        $data = ['documents'=> [['id'=> 1, 'text' => $text]]];

        $data = json_encode($data);

        curl_setopt($curl, CURLOPT_URL, 'https://hrblade.cognitiveservices.azure.com/text/analytics/v3.1/sentiment?opinionMining=true');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "Ocp-Apim-Subscription-Key: $key"
        ));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $result = @json_decode(curl_exec($curl), true);

        $success = false;

        if($result) {
            if(isset($result['documents'])) {
                if(isset($result['documents'][0])) {
                    $success['emotions'] = $result['documents'][0]['confidenceScores'];
                }
            }
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://hrblade.cognitiveservices.azure.com/text/analytics/v3.1/keyPhrases');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "Ocp-Apim-Subscription-Key: $key"
        ));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $result = @json_decode(curl_exec($curl), true);

        if($result) {
            if(isset($result['documents'])) {
                if(isset($result['documents'][0])) {
                    $success['keywords'] = $result['documents'][0]['keyPhrases'];
                }
            }
        }

        return $success;
    }
}
