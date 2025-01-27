<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Mail\InviteInterwiew;
use App\Models\EmailTemplate;
use App\Models\Job;
use App\Models\Link;
use App\Models\Response;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

class InviteController extends BaseController
{
    public function create(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email',
            'phone' => 'sometimes',
            'job_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $job = Job::find($data['job_id']);

        if (!$user->agency->companies->count() || !in_array($job->company_id, $user->agency->companies->pluck('id')->toArray())) {
            return $this->error(__('messages.job_not_found'));
        }

        if (!$job || !$job->active) {
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

        $exist = Response::where(function ($query) use ($data) {
            $query->orWhere('email', $data['email']);
        })->where('job_id', $job->id)->first();

        if ($exist) {
            return $this->error(__('messages.resume_exist'));
        }

        $data['invited'] = 1;
        $data['full'] = $data['name'];
        $data['hash'] = Str::random(26);
        while (Response::where('hash', $data['hash'])->first()) {
            $data['hash'] = Str::random(26);
        }
        $data['company_id'] = $job->company->id;
        $data['agency_id'] = $job->agency_id;
        $data['status'] = 'NEW';
        $data['language'] = 'en';

        if ($request->language) {
            if (in_array($request->language, ['ru', 'en', 'es', 'de'])) {
                $data['language'] = $request->language;
            }
        }

        if ($request->get('note')) {
            $data['note'] = $request->get('note');
        }

        unset($data['name']);

        $response = Response::create($data);



        $pipelines = $job->pipelines;

        if($job->pipelines->count()) {
            $response->pipeline_id = $pipelines->first()->id;
            $response->save();
        }

        if($request->get('filepond')) {
            $filepond = app(\Sopamo\LaravelFilepond\Filepond::class);
            $filepondPath = $filepond->getPathFromServerId($request->get('filepond'));
            $name = md5(\File::basename($filepondPath)) . '_' . rand(10, 10000);
            $extension = \File::extension($filepondPath);
            $path = "responses/{$response->id}/CV/$name.$extension";
            \File::makeDirectory(storage_path('app/public/' . "responses/{$response->id}/CV"), 0755, true, true);
            \File::move($filepondPath, storage_path('app/public/' . $path));
            $defaultCV = Storage::disk('public')->get($path);
            Storage::disk(env('FILESYSTEM_DRIVER'))->put($path, $defaultCV);
            Storage::disk(env('FILESYSTEM_DRIVER'))->setVisibility($path, 'public');
            \File::deleteDirectory("responses/{$response->id}");
            $response->default_cv = $path;
            $response->save();
        }

        if ($request->has('send_invite') && $request->get('send_invite') == 1) {
            $response->status = 'INVITED';
            $response->save();
            $link = new Link();
            $link->job_id = $job->id;
            $link->hash = Str::random(26);
            while (Link::where('hash', $link->hash)->first()) {
                $link->hash = Str::random(26);
            }
            $link->active = 1;
            $link->response_id = $response->id;
            $link->save();

            $language = 'en';

            if ($request->language) {
                if (in_array($request->language, ['ru', 'en', 'es', 'de'])) {
                    $language = $request->language;
                }
            }

            $company = $job->company;

            $template = EmailTemplate::where('type', 'INVITE')->where('company_id', $job->company_id)->where('language', $language)->first();

            if (!$template) {
                $template = EmailTemplate::where('type', 'INVITE')->where('default', 1)->where('language', $language)->first();
            }

            App::setLocale($language);

            if (isset($data['phone'])) {
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
                Mail::to($response->email)->send(new InviteInterwiew($link, $company, $template));
            } catch (\Exception $e) {
                Log::error("Invite email not send to {$response->email}");
            }


            if (\request()->header('Accept-Language') && in_array(\request()->header('Accept-Language'), ['ru', 'en', 'es', 'de'])) {
                App::setLocale(\request()->header('Accept-Language'));
            }

            if ($smsSended) {
                return $this->success($response, __('messages.invited'));
            }

            return $this->success($response, __('messages.invited_maybe_no_sms'));
        }

        return $this->success(__('messages.candidate_created'));
    }

    public function createFromCSV(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'csv' => 'required|file',
            'job_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $job = Job::find($data['job_id']);

        if (!$user->agency->companies->count() || !in_array($job->company_id, $user->agency->companies->pluck('id')->toArray())) {
            return $this->error(__('messages.job_not_found'));
        }

        if (!$job || !$job->active) {
            return $this->error(__('messages.job_not_found'));
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

        $responses = [];

        $twilioAccountSid = getenv("TWILIO_ACCOUNT_SID");
        $twilioApiKey = getenv("TWILIO_API_KEY");
        $twilioApiSecret = getenv("TWILIO_AUTH_SECRET");

        $twilio = new Client($twilioApiKey, $twilioApiSecret, $twilioAccountSid);

        $language = 'en';

        if ($request->language) {
            if (in_array($request->language, ['ru', 'en', 'es', 'de'])) {
                $language = $request->language;
            }
        }

        App::setLocale($language);

        if (($handle = fopen($request->file('csv'), "r")) !== FALSE) {
            $delimiters = array(
                ';' => 0,
                ',' => 0,
                "\t" => 0,
                "|" => 0
            );

            $firstLine = fgets($handle);
            foreach ($delimiters as $delimiter => &$count) {
                $count = count(str_getcsv($firstLine, $delimiter));
            }

            $delimiter = array_search(max($delimiters), $delimiters);

            fclose($handle);

            $handle = fopen($request->file('csv'), "r");

            $pipelines = $job->pipelines;

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (!isset($data[1]) || !filter_var($data[1], FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $exist = Response::where(function ($query) use ($data) {
                    $query->orWhere('email', $data[1]);
                })->where('job_id', $job->id)->first();

                if ($exist) {
                    continue;
                }
                $create['email'] = $data[1];
                $create['invited'] = 1;
                $create['full'] = $data[0];
                if (isset($data[2])) {
                    $create['phone'] = $data[2];
                }
                $create['hash'] = Str::random(26);
                while (Response::where('hash', $create['hash'])->first()) {
                    $create['hash'] = Str::random(26);
                }
                $create['language'] = $language;
                $create['job_id'] = $job->id;
                $create['company_id'] = $job->company->id;
                $create['agency_id'] = $job->agency_id;

                $response = Response::create($create);

                if($job->pipelines->count()) {
                    $response->pipeline_id = $pipelines->first()->id;
                    $response->save();
                }
                $a=1;
                // if ($request->has('send_invite') && $request->get('send_invite') == 1) {
                if ($a == 1) {
                    $link = new Link();
                    $link->job_id = $job->id;
                    $link->hash = Str::random(26);
                    while (Link::where('hash', $link->hash)->first()) {
                        $link->hash = Str::random(26);
                    }
                    $link->active = 1;
                    $link->response_id = $response->id;
                    $link->save();

                    /* try {
                         Mail::to($response->email)->send(new InviteInterwiew($link));
                     } catch (\Exception $e) {
                         Log::error("Invite email not send to {$response->email}");
                     }*/


                    /* new */
                    $company = $job->company;
                    $template = EmailTemplate::where('type', 'INVITE')->where('company_id', $job->company_id)->where('language', $language)->first();
                    if (!$template) {
                        $template = EmailTemplate::where('type', 'INVITE')->where('default', 1)->where('language', $language)->first();
                    }

                    try {
                        Mail::to($response->email)->send(new InviteInterwiew($link, $company, $template));
                    } catch (\Exception $e) {
                        Log::error("Invite email not send to {$response->email}");
                    }

                    /* new -- */

                    $baseUrl = env('APP_PAGE');

                    if (isset($data[2])) {
                        try {
                            SmsHelper::send(__('messages.sms_invite', ['company_name' => $job->company->name, 'job_name' => $job->name, 'link' => $baseUrl . 'i/' . $link->hash]), $create['phone']);
                        } catch (\Error $error) {
                            Log::error('Phone send ' . $create['phone']);
                        }
                    }

                }

                $responses[] = $response;
            }
            fclose($handle);
        }

        if (\request()->header('Accept-Language') && in_array(\request()->header('Accept-Language'), ['ru', 'en', 'es', 'de'])) {
            App::setLocale(\request()->header('Accept-Language'));
        }

        return $this->success($responses, __('messages.invited'));
    }
}
