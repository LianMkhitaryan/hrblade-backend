<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InterventionImage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Illuminate\Support\Facades\DB;

class FileSaver extends Controller
{
    public function handle($file, $slug, $width = null, $height = null, $quality = null)
    {
        $path = $slug . DIRECTORY_SEPARATOR . date('FY') . DIRECTORY_SEPARATOR;

        $filename = $this->generateFileName($file, $path);

        $image = InterventionImage::make($file);

        $fullPath = $path . $filename . '.' . $file->getClientOriginalExtension();

        $resize_width = null;
        $resize_height = null;
        if (!is_null($width) && !is_null($height)) {
            $resize_width = $width;
            $resize_height = $height;
        } else {
            $resize_width = $image->width();
            $resize_height = $image->height();
        }
        if (!is_null($quality)) {
            $resize_quality = $quality;
        } else {
            $resize_quality = 90;
        }

        $image = $image->resize(
            $resize_width,
            $resize_height,
            function (Constraint $constraint) {
                $constraint->aspectRatio();
            }
        )->orientate()->encode($file->getClientOriginalExtension(), $resize_quality);


        Storage::disk(env('FILESYSTEM_DRIVER', 'public'))->put($fullPath, (string)$image, 'public');

        return $fullPath;

    }

    public function saveByUrl($file, $slug, $width = null, $height = null, $quality = null)
    {
        $path = $slug . DIRECTORY_SEPARATOR . date('FY') . DIRECTORY_SEPARATOR;

        $filename = Str::random(20);

        $image = InterventionImage::make($file);

        $mime = $image->mime();  //edited due to updated to 2.x
        if ($mime == 'image/jpeg')
            $extension = 'jpg';
        elseif ($mime == 'image/png')
            $extension = 'png';
        elseif ($mime == 'image/gif')
            $extension = 'gif';
        else
            $extension = '';

        // Make sure the filename does not exist, if it does, just regenerate
        while (Storage::disk(env('FILESYSTEM_DRIVER', 'public'))->exists($path . $filename . '.' . $extension)) {
            $filename = Str::random(20);
        }

        $fullPath = $path . $filename . '.' . $extension;

        $resize_width = null;
        $resize_height = null;
        if (!is_null($width) && !is_null($height)) {
            $resize_width = $width;
            $resize_height = $height;
        } else {
            $resize_width = $image->width();
            $resize_height = $image->height();
        }
        if (!is_null($quality)) {
            $resize_quality = $quality;
        } else {
            $resize_quality = 90;
        }

        $image = $image->resize(
            $resize_width,
            $resize_height,
            function (Constraint $constraint) {
                $constraint->aspectRatio();
//                    if (isset($this->options->upsize) && !$this->options->upsize) {
//                        $constraint->upsize();
//                    }
            }
        )->orientate()->encode($extension, $resize_quality);


        Storage::disk(env('FILESYSTEM_DRIVER', 'public'))->put($fullPath, (string)$image, 'public');

        return $fullPath;
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param $path
     *
     * @return string
     */
    protected function generateFileName($file, $path)
    {

        $filename = Str::random(20);

        // Make sure the filename does not exist, if it does, just regenerate
        while (Storage::disk(env('FILESYSTEM_DRIVER', 'public'))->exists($path . $filename . '.' . $file->getClientOriginalExtension())) {
            $filename = Str::random(20);
        }

        return $filename;
    }

    public function fromRequest(Request $request)
    {
        return '/storage/' . $this->inputFile($request->file)['src'];
    }

    public function saveFile($file, $slug)
    {

        //$path = $slug . DIRECTORY_SEPARATOR . date('FY') . DIRECTORY_SEPARATOR;
       // $filename = $this->generateFileName($file, $path);

       // $fullPath = $path . $filename . '.' . $file->getClientOriginalExtension();



        $fileSaved = Storage::disk(env('FILESYSTEM_DRIVER'))->put($slug, $file );

        Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($fileSaved,'public');

        return $fileSaved;
    }

    public function inputFile($file)
    {
        $mime = $file->getMimeType();

        if ($mime == 'image/jpeg' || $mime == 'image/png' || $mime == 'image/gif') {
            $save['src'] = $this->handle($file, 'message', 500, 500, 90);
            $save['type'] = 'image';
            $save['name'] = $file->getClientOriginalName();
            $save['size'] = $file->getSize();

        } elseif ($mime == 'audio/wave' || $mime == 'audio/x-wav' || $mime == 'audio/ogg' || $mime == 'audio/x-hx-aac-adts' || $mime == 'audio/acc') {
            $save['src'] = $this->saveAudio($file, 'message');
            $save['type'] = 'audio';
            $save['name'] = $file->getClientOriginalName();
            $save['size'] = $file->getSize();
        } elseif ($mime == 'audio/mp3' || $mime == 'audio/mpeg') {
            $save['src'] = $this->saveFile($file, 'message');
            $save['type'] = 'audio';
            $save['name'] = $file->getClientOriginalName();
            $save['size'] = $file->getSize();
        }  else {
            $extension = $file->getClientOriginalExtension();
            $save['src'] = $this->saveFile($file, 'message');
            $save['type'] = $extension;
            $save['name'] = $file->getClientOriginalName();
            $save['size'] = $file->getSize();
        }
//        } elseif ($mime == 'audio/mpeg') {
//            $save['src'] = $this->saveFile($file, 'message');
//            $save['type'] = 'audio';
//        } elseif ($mime == 'image/vnd.djvu') {
//            $save['src'] = $this->saveFile($file, 'message');
//            $save['type'] = 'djvu';
//        }

        return $save;
    }


}
