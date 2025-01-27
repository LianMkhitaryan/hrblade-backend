<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoomsController extends BaseController
{
    public function rooms()
    {
        $user = Auth::user();

        if($user->isOwner()) {
            $companies = $user->agency->companies;
        } else {
            $permissions = Permission::where('user_id', $user->id)->where('name','view_rooms')->get();
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

        $rooms = Room::whereIn('company_id', $companies->pluck('id'))->orderBy('created_at','desc')->get();

        return $this->success($rooms);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'sometimes',
            'start' => 'required|date',
            'status' => 'sometimes',
            'source' => 'required',
            'link' => 'sometimes',
            'company_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('create_rooms', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $data = $request->all();

        $data['hash'] = Str::random(64);

        $room = Room::create($data);

        return $this->success($room,__('messages.room_created'));
    }

    public function edit(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'sometimes',
            'start' => 'required|date',
            'source' => 'required',
            'status' => 'sometimes',
            'link' => 'sometimes',
            'room_id' => 'required',
            'company_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_rooms', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $room = Room::find((integer) $request->room_id);

        $data = $request->all();

        unset($data['room_id']);

        $room->update($data);

        return $this->success($room, __('messages.room_edited'));
    }

    public function get($hash)
    {
        $room = Room::where('hash', $hash)->first();

        if(!$room) {
            return $this->error(__('messages.room_not_found'));
        }

        $room->company_name = $room->company->name;

        unset($room->company);

        return $this->success($room);
    }

    public function delete(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'room_id' => 'required',
            'company_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $company = Company::find($request->company_id);

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('edit_rooms', $company->id)) {
            return $this->error(__('messages.not_have_permissions'));
        }

        $room = Room::find((integer) $request->room_id);

        $room->delete();

        return $this->success([], __('messages.room_deleted'));
    }
}
