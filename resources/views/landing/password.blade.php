@extends('landing.layouts.main')

@section('content')
    <div class="form__container">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-5 mb-md-0">
                    <div class="fs-35 fw-semibold font-montserrat mb-4 mb-lg-5 pb-3 pb-lg-5">Reset your password</div>
                    <div class="fs-20">If you don’t have an account <br>
                        You can <a href="" class="color-blue fw-semibold">Sign In</a></div>
                    <img src="/landing/img/signin.png" alt="" class="form__container--img">
                </div>
                <div class="col-md-6 col-lg-5 offset-lg-1  mb-md-0 tac">
                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <input type="text" name="email" placeholder="Email Address">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <button class="button-blue mb-4">Reset Password</button>
                    </form>
                    <img src="/landing/img/signin2.png" alt="" class="d-inline-block mx-auto d-md-none">
                </div>
            </div>
        </div>
    </div>
@endsection