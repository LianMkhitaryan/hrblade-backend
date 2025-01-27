<h4>Help</h4>
<br/>
<p>Subject: {{$request->subject}}</p>
<br/>
@if($request->email)
    <p>Email: {{$request->email}}</p>
    <br/>
@endif
<p>Description: {{$request->description}}</p>
