<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Help;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class HelpsController extends BaseController
{
    public function create(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'subject' => 'required|max:255',
            'email' => 'required|email',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $data = $request->all();

        $data['status'] = 'NEW';
        if($user) {
            $data['user_id'] = $user->id;
        }

       Help::create($data);

        try {
            Mail::to(env('HELP_EMAIL'))->send(new \App\Mail\Help($request));
        } catch (\Exception $e) {
            Log::error("Invite email not send to help");
            return $this->error([],__('messages.error'));
        }

        return $this->success([],__('messages.help_created'));
    }
}
