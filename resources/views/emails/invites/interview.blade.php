@component('mail::message')
# @lang('messages.hello')

@lang('messages.email_invite', ['company_name' => $link->job->company->name, 'job_name' => $link->job->name])

@component('mail::button', ['url' => env('APP_PAGE') . "i/" . $link->hash])
    @lang('messages.interview')
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
