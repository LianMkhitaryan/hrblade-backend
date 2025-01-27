<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentsController extends BaseController
{
    public function create(Request $request)
    {
        $user = Auth::user();

        if(!$request->message) {
            return $this->error(__('messages.need_message'));
        }

        if(!$request->response_id) {
            return $this->error(__('messages.need_response_id'));
        }

        $response = Response::find((int) $request->response_id);

        if(!$response) {
            return $this->error(__('messages.response_not_found'));
        }

        $company = $response->job->company;

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(!$user->perm('rate_responses', $company->id)) {
            return $this->error(__('messages.cannot_rate_response'));
        }

        $comment = new Comment();
        $comment->response_id = $response->id;
        $comment->user_id = $user->id;
        $comment->comment = $request->message;
        $comment->save();

        $comment->user;

        return $this->success($comment, __('messages.comment_created'));
    }

    public function remove($id)
    {
        $user = Auth::user();

        $comment = Comment::find((int) $id);

        if(!$comment) {
            return $this->error( __('messages.comment_not_found'));
        }

        $response = $comment->response;

        if(!$response) {
            return $this->error(__('messages.response_not_found'));
        }

        $company = $response->job->company;

        if(!$company) {
            return $this->error(__('messages.company_not_found'));
        }

        if(($user->isOwner() && $company->agency_id == $user->agency_id) || $comment->user_id == $user->id) {
            $comment->delete();
            return $this->success(__('messages.comment_deleted'));
        }

        return $this->error(__('messages.cannot_remove_comment'));
    }
}
