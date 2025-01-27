@extends('landing.layouts.main')

@section('content')
    <div class="form__container">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-5 mb-md-0">
                    <div class="fs-35 fw-semibold font-montserrat mb-4 mb-lg-5 pb-3 pb-lg-5">Sign in</div>
                    <div class="fs-20">If you don’t have an account <br>
                        You can <a href="{{route('page', 'register')}}" class="color-blue fw-semibold">Register here</a></div>
                    <img src="/landing/img/signin.png" alt="" class="form__container--img">
                </div>
                <div class="col-md-6 col-lg-5 offset-lg-1  mb-md-0 tac">

                    <div class="fs-20">Check your email <span  class="color-blue fw-semibold">{{\Illuminate\Support\Facades\Auth::user()->email}}</span> to confirm it</div>

                    <br>
                    <div class="fs-20 mb-5">Or logout</div>
                    <form method="post" action="{{route('logout')}}">
                        @csrf
                        <button class="button-blue">
                         Log out
                        </button>
                    </form>


                    <img src="/landing/img/signin2.png" alt="" class="d-inline-block mx-auto d-md-none">
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