<?php
/**
 * Created by PhpStorm.
 * User: skyincom
 * Date: 2/28/20
 * Time: 8:16 AM
 */
namespace App\Traits;

use App\Models\Response;
use Aws\Credentials\Credentials;
use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Carbon\Carbon;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as FFMpeg;
use Illuminate\Http\Request;


trait FileUploadS3Trait
{
    public function uploadAnswerVideo($file, Response $response, $question)
    {
        $path = $file->store("responses/{$response->id}/{$question->id}/video",'public');
        $data_arr=[];

        $name=md5($file->getClientOriginalName()).'_'.rand(10,10000);

        $extension = $file->extension();

        $path_mp4 = $this->convertToMp4($path,$name, $extension, $response, $question);

        if(!$path_mp4){
            return false;
        }

        $transcoded = $this->transcoded($name, $extension, $response, $question);

        $thumb=$this->getVideoThumb($path,$name,$response,$question);
        $time=$this->getVideoTime($path);
        $gif = $this->getVideoGif($path,$name,$response,$question);
        $data_arr=['download_link'=>str_replace('public/','',$path_mp4),
            'original_name'=>$file->getClientOriginalName(),
            'transcoded' => $transcoded,
            'thumb'=>$thumb,
            'time'=>$time,
            'gif' => $gif,
            'upload'=>Carbon::now()
        ];
        //$out=['file'=>json_encode($data_arr),'thumb'=>$thumb,'time'=>$time];
        Storage::disk('public')->deleteDirectory("/responses/{$response->id}/{$question->id}");
        return $data_arr;
    }

    public function uploadQuestionVideo($file, $question)
    {
        $path = $file->store("questions/{$question->id}/video",'public');
        $data_arr=[];

        $name=md5($file->getClientOriginalName()).'_'.rand(10,10000);

        $extension = $file->extension();

        $path_mp4 = $this->convertToMp4Question($path,$name, $extension,$question);

        if(!$path_mp4){
            return false;
        }

        $transcoded = $this->transcodedQuestion($name, $extension,$question);

        $data_arr=['download_link'=>str_replace('public/','',$path_mp4),
            'original_name'=>$file->getClientOriginalName(),
            'transcoded' => $transcoded,
            'upload'=>Carbon::now()
        ];
        //$out=['file'=>json_encode($data_arr),'thumb'=>$thumb,'time'=>$time];
        Storage::disk('public')->deleteDirectory("/questions/{$question->id}");
        return $data_arr;
    }

    public function convertToMp4Question($path,$name, $extension, $question)
    {
        $saveUrl = "questions/{$question->id}/video/$name.$extension";

        try {
            Storage::disk(env('FILESYSTEM_DRIVER'))->put($saveUrl,file_get_contents(storage_path('app/public/'.$path)));
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return false;
        }
        return $saveUrl;
    }

    public function convertToMp4($path,$name, $extension, $response, $question)
    {
        $saveUrl = "responses/{$response->id}/{$question->id}/video/$name.$extension";

        try {
            Storage::disk(env('FILESYSTEM_DRIVER'))->put($saveUrl,file_get_contents(storage_path('app/public/'.$path)));
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return false;
        }
        return $saveUrl;
    }

    public function transcodedQuestion($name, $extension, $question)
    {
        $saveUrl = "questions/{$question->id}/video/$name.$extension";

        try {
            $transcoder = new ElasticTranscoderClient([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => env('AWS_VERSION_CODER'),
            ]);

            $preset = null;

            try {
                $preset = $question->job->agency->video_definition;
            } catch (\Exception $e) {
                Log::error('Preset not found');
            }

            if(!$preset) {
                $preset = env('AWS_PRESET');
            }
            Log::info("Preset: $preset");
            $transCodingUrl = "questions/{$question->id}/video/transcoded/$name.mp4";
            $transcoder->createJob(array(
                'PipelineId' => env('AWS_PIPELINE'),
                'Input' => array(
                    'Key' => $saveUrl,
                    'FrameRate' => 'auto',
                    'Resolution' => 'auto',
                    'AspectRatio' => 'auto',
                    'Interlaced' => 'auto',
                    'Container' => 'auto',
                ),
                'Output' => array(
                    'Key' => $transCodingUrl,
                    'PresetId' =>  $preset,
                ),
            ));
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return false;
        }
        return $transCodingUrl;
    }

    public function transcoded($name, $extension, $response, $question)
    {
        $saveUrl = "responses/{$response->id}/{$question->id}/video/$name.$extension";

        try {
            $transcoder = new ElasticTranscoderClient([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => env('AWS_VERSION_CODER'),
            ]);

            $preset = null;

            try {
                $preset = $question->job->agency->video_definition;
            } catch (\Exception $e) {
               Log::error('Preset not found');
            }

            if(!$preset) {
                $preset = env('AWS_PRESET');
            }
            Log::info("Preset: $preset");
            $transCodingUrl = "responses/{$response->id}/{$question->id}/video/transcoded/$name.mp4";
            $transcoder->createJob(array(
                'PipelineId' => env('AWS_PIPELINE'),
                'Input' => array(
                    'Key' => $saveUrl,
                    'FrameRate' => 'auto',
                    'Resolution' => 'auto',
                    'AspectRatio' => 'auto',
                    'Interlaced' => 'auto',
                    'Container' => 'auto',
                ),
                'Output' => array(
                    'Key' => $transCodingUrl,
                    'PresetId' =>  $preset,
                ),
            ));
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return false;
        }
        return $transCodingUrl;
    }

    public function getVideoThumb($path,$name, $response,$question)
    {
       // $path=str_replace('public/','',$path);
        $saveUrl = "responses/{$response->id}/{$question->id}/thumbs/{$name}.jpg";

        if($question->isRus()) {
            FFMpeg::fromDisk('public')->open($path)->getFrameFromSeconds(0)
                ->export()
                ->toDisk(env('FILESYSTEM_DRIVER_YA'))
                ->save($saveUrl);
        } else {
            FFMpeg::fromDisk('public')->open($path)->getFrameFromSeconds(0)
                ->export()
                ->toDisk(env('FILESYSTEM_DRIVER'))
                ->save($saveUrl);
        }

        return $saveUrl;
    }

    public function getVideoGif($path,$name, $response,$question)
    {
        $path =storage_path('app/public/' . $path);
        $ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => env('FFMPEG_BINARIES','C:\ffmpeg\bin\ffmpeg.exe'),
            'ffprobe.binaries' => env('FFPROBE_BINARIES','C:\ffmpeg\bin\ffprobe.exe'),
        ]);
        $saveUrl =storage_path("/app/public/responses/{$response->id}/{$question->id}/gifs/{$name}.gif");
        $saveS3 = "/responses/{$response->id}/{$question->id}/gifs/{$name}.gif";

        $folderPath = storage_path("app/public/responses/{$response->id}/{$question->id}/gifs");
        if(!File::exists($folderPath)) {
            File::makeDirectory($folderPath, $mode = 0755, true, false);
        }

        $video = $ffmpeg->open($path);
        $video->gif(TimeCode::fromSeconds(0), new Dimension(320, 300), 2)
            ->save($saveUrl);

        if($question->isRus()) {
            Storage::disk(env('FILESYSTEM_DRIVER_YA'))->put($saveS3,file_get_contents($saveUrl));
        } else {
            Storage::disk(env('FILESYSTEM_DRIVER'))->put($saveS3,file_get_contents($saveUrl));
        }

        $returnUrl = "responses/{$response->id}/{$question->id}/gifs/{$name}.gif";
        return $returnUrl;
    }

    public function getVideoTime($path)
    {
        try {
            $time=FFMpeg::fromDisk('public')->open($path)->getDurationInSeconds();
            $data=Carbon::parse('17.11.1984 00:00')->addSeconds($time);
            return $data->format('i:s');
        } catch (\Error $e) {
            return null;
        }
    }

}
