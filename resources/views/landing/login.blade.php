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
                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <input type="text" name="email" value="{{old('email')}}" placeholder="Email Address" required>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <input type="password" name="password" placeholder="Password" required>
                        <div class="row pt pt-4 align-items-center">
                            <div class="col-6">
                                <button class="button-blue">Log In</button>
                            </div>
                            <div class="col-6 tac">
                                <a href="{{route('page', 'password')}}" class="color-gray">Forgot Password</a>
                            </div>
                        </div>
                        <div class="form__container--or my-4 my-lg-5">
                            <span>or contine with</span>
                        </div>
                        <div class="row mb-4">
                            <div class="col-6">
                                <a href="{{route('social.get', 'linkedin')}}" class="button-auth">
                                    <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                        <g id="Web" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <g id="0-1-Sign-in" transform="translate(-886.000000, -524.000000)" fill="#0077B5" fill-rule="nonzero">
                                                <g id="ui/button/big/main" transform="translate(827.000000, 509.000000)">
                                                    <g id="Group-11">
                                                        <g id="ui/linkedin" transform="translate(59.000000, 15.000000)">
                                                            <g id="linkedin">
                                                                <path d="M23.994,24 L23.994,23.999 L24,23.999 L24,15.197 C24,10.891 23.073,7.574 18.039,7.574 C15.619,7.574 13.995,8.902 13.332,10.161 L13.262,10.161 L13.262,7.976 L8.489,7.976 L8.489,23.999 L13.459,23.999 L13.459,16.065 C13.459,13.976 13.855,11.956 16.442,11.956 C18.991,11.956 19.029,14.34 19.029,16.199 L19.029,24 L23.994,24 Z" id="Path"></path>
                                                                <polygon id="Path" points="0.396 7.977 5.372 7.977 5.372 24 0.396 24"></polygon>
                                                                <path d="M2.882,0 C1.291,0 0,1.291 0,2.882 C0,4.473 1.291,5.791 2.882,5.791 C4.473,5.791 5.764,4.473 5.764,2.882 C5.763,1.291 4.472,4.4408921e-16 2.882,0 Z" id="Path"></path>
                                                            </g>
                                                        </g>
                                                    </g>
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                    Linkedin
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{route('social.get', 'google')}}" class="button-auth">
                                    <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                        <g id="Web" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <g id="0-1-Sign-in" transform="translate(-1137.000000, -524.000000)" fill-rule="nonzero">
                                                <g id="ui/button/big/main" transform="translate(1071.000000, 509.000000)">
                                                    <g id="Group-11">
                                                        <g id="search" transform="translate(66.000000, 15.000000)">
                                                            <path d="M5.31890625,14.5035 L4.4835,17.6221875 L1.43010938,17.6867813 C0.51759375,15.9942656 0,14.0578125 0,12 C0,10.0101094 0.4839375,8.13360938 1.34175,6.4813125 L1.34240625,6.4813125 L4.06078125,6.9796875 L5.25159375,9.68175 C5.00235938,10.4083594 4.86651562,11.1883594 4.86651562,12 C4.86660938,12.880875 5.02617188,13.7248594 5.31890625,14.5035 Z" id="Path" fill="#FBBB00"></path>
                                                            <path d="M23.7903281,9.75825 C23.9281406,10.4841563 24,11.2338281 24,12 C24,12.859125 23.9096719,13.6971563 23.7375938,14.5055156 C23.1534375,17.2562813 21.6270469,19.65825 19.5125625,21.3580312 L19.5119062,21.357375 L16.0879687,21.1826719 L15.603375,18.1575938 C17.0064375,17.33475 18.1029375,16.0470469 18.6805313,14.5055156 L12.2638125,14.5055156 L12.2638125,9.75825 L18.7741406,9.75825 L23.7903281,9.75825 Z" id="Path" fill="#518EF8"></path>
                                                            <path d="M19.5118594,21.357375 L19.5125156,21.3580313 C17.4560625,23.0109844 14.8437187,24 12,24 C7.43010937,24 3.4569375,21.4457344 1.43010937,17.6868281 L5.31890625,14.5035469 C6.33229687,17.2081406 8.9413125,19.1334375 12,19.1334375 C13.3147031,19.1334375 14.5463906,18.7780313 15.6032812,18.1575938 L19.5118594,21.357375 Z" id="Path" fill="#28B446"></path>
                                                            <path d="M19.6595625,2.762625 L15.7720781,5.94525 C14.67825,5.26153125 13.38525,4.8665625 12,4.8665625 C8.87207812,4.8665625 6.21426562,6.88017188 5.25164062,9.68175 L1.34240625,6.4813125 L1.34175,6.4813125 C3.33890625,2.63076562 7.3621875,0 12,0 C14.9116406,0 17.5813125,1.03715625 19.6595625,2.762625 Z" id="Path" fill="#F14336"></path>
                                                        </g>
                                                    </g>
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                    Google
                                </a>
                            </div>
                        </div>
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