@component('mail::message')

    @php
        $companiesNames = '';
        if(count($companies) > 0) {
            $first = true;
             foreach ($companies as $company) {
                 if($first) {
                       $companiesNames .= $company->name;
                       $first = false;
                 } else {
                      $companiesNames .= ", " . $company->name;
                 }
             }
        } else {
             $companiesNames = 'HRBlade';
        }
    @endphp
    {{__('messages.invite_to_company_text', ['company' => $companiesNames])}}

@component('mail::button', ['url' => env('APP_PAGE') . "registration/" . $invite->hash ])
    @lang('messages.register')
@endcomponent

@endcomponent
