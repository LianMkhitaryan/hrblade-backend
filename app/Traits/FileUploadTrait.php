<?php
/**
 * Created by PhpStorm.
 * User: skyincom
 * Date: 2/28/20
 * Time: 8:16 AM
 */
namespace App\Traits;

use App\Models\Response;
use Carbon\Carbon;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as FFMpeg;
use Illuminate\Http\Request;


trait FileUploadTrait
{

    public function uploadVideo(Request $request, string $key)
    {
        $path = $request->file($key)->store('public/stars/video');
        $file=$request->file($key);
        $data_arr=[];
        $data_arr[]=['download_link'=>str_replace('public/','',$path),'original_name'=>$file->getClientOriginalName()];
        return json_encode($data_arr);
    }

    public function uploadAnswerVideo($file, Response $response, $question)
    {
        $path =$file->store("public/responses/{$response->id}/{$question->id}/original");
        $data_arr=[];

        $name=md5($file->getClientOriginalName()).'_'.rand(10,10000);

        $path_mp4=$this->convertToMp4($path,$name, $response,$question);
        if(!$path_mp4){
            return false;
        }

        $thumb=$this->getVideoThumb($path_mp4,$name,$response,$question);
        $time=$this->getVideoTime($path_mp4);
        $gif = $this->getVideoGif($path_mp4,$name,$response,$question);
        $data_arr=['download_link'=>str_replace('public/','',$path_mp4),
            'original_name'=>$file->getClientOriginalName(),
            'thumb'=>$thumb,
            'time'=>$time,
            'gif' => $gif,
            'upload'=>Carbon::now()->format('d m Y')];
        //$out=['file'=>json_encode($data_arr),'thumb'=>$thumb,'time'=>$time];
        return $data_arr;
    }

    public function uploadImage(Request $request,string $key, $star = null)
    {
        if($star) {
            $path = $request->file($key)->store("public/stars/{$star->slug}/photos");
        } else {
            $path = $request->file($key)->store('public/stars/photos');
        }

        return str_replace('public/','',$path);
    }

    public function uploadOrderVideo(Request $request,string $key, Response $star = null)
    {
        $path = $request->file($key)->store('public/stars/video/original');
        $file=$request->file($key);
        $data_arr=[];
        $name=md5($file->getClientOriginalName()).'_'.rand(10,10000);
        $path_mp4=$this->convertToMp4($path,$name,$star);
        if(!$path_mp4){
            return false;
        }
        $thumb=$this->getVideoThumb($path,$name,$star);
        $time=$this->getVideoTime($path);
        $gif = $this->getVideoGif($path,$name,$star);
        $data_arr[]=['gif' => $gif,'download_link'=>str_replace('public/','',$path_mp4),'original_name'=>$file->getClientOriginalName()];
        $out=['file'=>json_encode($data_arr),'thumb'=>$thumb,'time'=>$time];
        return $out;
    }

    public function convertToMp4($path,$name, $response, $question)
    {
        $path=str_replace('public/','',$path);
        $saveUrl = "responses/{$response->id}/{$question->id}/mp4/{$name}.mp4";
        $folderPath = storage_path("app/public/responses/{$response->id}/{$question->id}/mp4");
        if(!File::exists($folderPath)) {
            File::makeDirectory($folderPath, $mode = 0755, true, false);
        }
        try {
            FFMpeg::fromDisk('public')->open($path)->export()->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264'))->save($saveUrl);
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return false;
        }
        return $saveUrl;
    }

    public function getVideoThumb($path,$name, $response,$question)
    {
        $path=str_replace('public/','',$path);

        $saveUrl = "responses/{$response->id}/{$question->id}/thumbs/{$name}.jpg";
        $folderPath = storage_path("app/public/responses/{$response->id}/{$question->id}/thumbs");
        if(!File::exists($folderPath)) {
            File::makeDirectory($folderPath, $mode = 0755, true, false);
        }

        FFMpeg::fromDisk('public')->open($path)->getFrameFromSeconds(0)
            ->export()
            ->save($saveUrl);
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

        $folderPath = storage_path("app/public/responses/{$response->id}/{$question->id}/gifs");
        if(!File::exists($folderPath)) {
            File::makeDirectory($folderPath, $mode = 0755, true, false);
        }
        $video = $ffmpeg->open($path);
        $video->gif(TimeCode::fromSeconds(0), new Dimension(320, 300), 2)
            ->save($saveUrl);
        $returnUrl = "responses/{$response->id}/{$question->id}/gifs/{$name}.gif";

        return $returnUrl;
    }

    public function getVideoTime($path)
    {
        $path=str_replace('public/','',$path);
        $time=FFMpeg::fromDisk('public')->open($path)->getDurationInSeconds();
        $data=Carbon::parse('17.11.1984 00:00')->addSeconds($time);
        return $data->format('i:s');
    }

}
