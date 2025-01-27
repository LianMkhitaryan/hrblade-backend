@extends('landing.layouts.main')

@section('content')
    <div class="form__container">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-5 mb-md-0 ">
                    <div class="fs-35 fw-semibold font-montserrat mb-3  ">Sign up</div>
                    <div class="fs-20 mb-4 mb-lg-5 pb-3">Get started for free. No credit card required.</div>
                    <div class="fs-20">If you don’t have an account <br>
                        You can <a href="{{route('page', 'login')}}" class="color-blue fw-semibold">Sign In</a></div>
                    <img src="/landing/img/signup.png" alt="" class="form__container--img">
                </div>
                <div class="col-md-6 col-lg-5 offset-lg-1 mb-4 mb-md-0 tac">
                    <div class="fs-20 mb-5" style="text-align: left;">You are invited to the agency <span class="color-blue fw-semibold">{{$invite->agency->name}}</span>
                        <br>
                        <br>
                        with email: <span class="color-blue fw-semibold">{{$invite->email}}</span>
                    </div>
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <input type="text" placeholder="{{ __('Name') }}"  value="{{old('name')}}" name="name" required autofocus autocomplete="name">
                        <input type="hidden" value="{{$invite->hash}}" name="hash">
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <input  type="hidden" name="email" value="{{$invite->email}}" placeholder="Email Address">
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <div class="relative">
                            <svg class="eye-pass" width="15px" height="13px" viewBox="0 0 15 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <g id="Web" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g id="0-2-Registration" transform="translate(-1270.000000, -367.000000)" class="fill" fill="#B6B7C6" fill-rule="nonzero">
                                        <g id="Group-11" transform="translate(827.000000, 346.000000)">
                                            <g id="ui/eye" transform="translate(443.000000, 21.000000)">
                                                <g id="enable" transform="translate(0.000000, 0.875000)">
                                                    <path d="M14.845,4.841875 C13.625625,1.900625 10.7425,0 7.5,0 C4.2575,0 1.374375,1.900625 0.155,4.841875 C-0.05125,5.339375 -0.05125,5.91 0.155,6.408125 C1.374375,9.349375 4.2575,11.25 7.5,11.25 C10.7425,11.25 13.625625,9.349375 14.845,6.408125 C15.05125,5.910625 15.05125,5.339375 14.845,4.841875 Z M7.5,8.125 C6.12125,8.125 5,7.00375 5,5.625 C5,4.24625 6.12125,3.125 7.5,3.125 C8.87875,3.125 10,4.24625 10,5.625 C10,7.00375 8.87875,8.125 7.5,8.125 Z" id="Shape"></path>
                                                </g>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                            <input placeholder="Password" type="password" name="password" required >
                        </div>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <button class="button-blue">Sign Up</button>
                    </form>
                    <img src="/landing/img/signup2.png" alt="" class="d-block mx-auto d-md-none">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @if(session('token'))
        <script>
            localStorage.setItem('token', '{{session('token')}}')
            window.location.href = '{{env('APP_PAGE')}}';
        </script>
    @endif
@endsection