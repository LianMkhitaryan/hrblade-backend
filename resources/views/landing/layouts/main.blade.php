<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>@if(isset($title)){{$title}} - @endif()HRBlade</title>
    <meta name="description" content="@if(isset($description)) {{$description}} @else HRBlade @endif()">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" sizes="180x180" href="/landing/img/fav/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/landing/img/fav/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/landing/img/fav/favicon-16x16.png">
    <link rel="manifest" href="/landing/img/fav/site.webmanifest">
    <link rel="shortcut icon" href="/landing/img/fav/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="/landing/img/fav/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="/landing/css/style.min.css?v=5" rel="stylesheet" media="screen">

    <link rel="stylesheet" href="https://cdn.plyr.io/3.6.2/plyr.css" />
    <!-- <link rel="preload" href="fonts/*.woff2" as="font" type="font/woff2" crossorigin> -->
</head>

<body>
<header class="header">
    <div class="container container-max-width">
        <div class="row align-items-center py-3 py-md-4">
            <div class="col-sm-auto col-md-2 col-lg-auto col-3">
                <a href="/"><img src="" class="header__logo" data-logo1="/landing/img/Logo.svg" data-logo2="/landing/img/Logo2.svg" alt=""></a>
            </div>
            <div class="col  menu">
                <div class="row flex-column flex-md-row align-items-center  justify-content-center">
                    <div class="col-auto"><a href="{{route('page', 'features')}}" class="header__link @if($view == 'features') active @endif">Features</a></div>
                    <div class="col-auto"><a href="{{route('page', 'pricing')}}" class="header__link  @if($view == 'pricing') active @endif">Pricing</a></div>
                    <div class="col-auto"><a href="{{route('page', 'about')}}" class="header__link  @if($view == 'about') active @endif">About</a></div>
                    <div class="col-auto"><a href="{{route('page', 'faq')}}" class="header__link  @if($view == 'faq') active @endif">FAQ</a></div>
                    <div class="col-auto mb-4 mb-md-0"><a href="{{route('page', 'contacts')}}" class="header__link  @if($view == 'contacts') active @endif">Contacts</a></div>
                    @if(\Illuminate\Support\Facades\Auth::user())
                        <div class="col-auto d-block d-md-none">
                            <a href="{{route('dashboardapp')}}" class="header__link mb-3">Dashboard</a>
                        </div>
                    @else
                        <div class="col-auto d-block d-md-none">
                            <a href="{{route('page', 'login')}}" class="header__link mb-3">Sign in</a>
                        </div>
                        <div class="col-12 d-block d-md-none mb-4">
                            <a href="{{route('page', 'register')}}" class="button-white">Register</a>
                        </div>

                    @endif
                </div>
            </div>
            @if(\Illuminate\Support\Facades\Auth::user())
                <div class="col-auto d-none d-md-block">
                    <a href="{{route('dashboardapp')}}" class="button-white">Dashboard</a>
                </div>
            @else
                <div class="col-auto ml-auto d-none d-md-block">
                    <a href="{{route('page', 'login')}}" class="header__link ">Sign in</a>
                </div>
                <div class="col-auto d-none d-md-block">
                    <a href="{{route('page', 'register')}}" class="button-white">Register</a>
                </div>
            @endif
            <div class="col-auto d-md-none ml-auto">
                <button class="hamburger hamburger--elastic" type="button" aria-label="Menu" aria-controls="navigation">
            <span class="hamburger-box">
              <span class="hamburger-inner"></span>
            </span>
                </button>
            </div>
        </div>
    </div>
</header>

@yield('content')

<footer class="footer">
    <div class="container container-max-width">
        <div class="row align-items-center">
            <div class="col-sm-auto mb-4 mb-md-4">
                <a href="/"><img src="/landing/img/Logo.svg" alt=""></a>
            </div>
            <div class="col-sm mb-3 mb-md-4">
                <div class="row justify-content-md-center">
                    <div class="col-sm-auto mb-4 mb-sm-0"><a href="{{route('page', 'features')}}" class="footer__link">Features</a></div>
                    <div class="col-sm-auto mb-4 mb-sm-0"><a href="{{route('page', 'pricing')}}" class="footer__link">Pricing</a></div>
                    <div class="col-sm-auto mb-4 mb-sm-0"><a href="{{route('page', 'about')}}" class="footer__link">About</a></div>
                    <div class="col-sm-auto mb-4 mb-sm-0"><a href="{{route('page', 'faq')}}" class="footer__link">FAQ</a></div>
                    <div class="col-sm-auto mb-4 mb-sm-0"><a href="{{route('page', 'contacts')}}" class="footer__link">Contacts</a></div>
                </div>
            </div>
            <div class="col-auto mb-4 mb-md-4">
                <div class="row mx-n1">
                    <div class="col-auto px-1"><a href="" class="footer__social"><i class="fab fa-facebook-f"></i></a></div>
                    <div class="col-auto px-1"><a href="" class="footer__social"><i class="fab fa-instagram"></i></a></div>
                    <div class="col-auto px-1"><a href="" class="footer__social"><i class="fab fa-twitter"></i></a></div>
                </div>
            </div>
        </div>
        <div class="row align-items-center px-n1 px-3">
            <div class="col-auto fs-13 color-gray mb-3 px-1">Global on-demand video interviewing, communication and recruitment platform</div>
            <div class="col-auto  mb-3 px-1"><a href="{{route('page', 'privacy')}}" class="fs-13 color-gray tdu">Privacy Policy</a></div>
            <div class="col-auto  mb-3 px-1"><a href="{{route('page', 'terms')}}" class="fs-13 color-gray tdu">Terms and Conditions</a></div>
            <div class="col-auto  mb-3 px-1"><a href="{{route('page', 'refund')}}" class="fs-13 color-gray tdu">Refund/Return Policy</a></div>
            <div class="col-auto fs-13 color-gray ml-md-auto mb-3 px-1">Developed by <a href="" target="_blank" class="tdu color-gray">SKYINCOM</a></div>
        </div>
    </div>
</footer>

<!-- <script src="https://unpkg.com/ionicons@4.4.4/dist/ionicons.js"></script> -->
<script src="/landing/js/script.min.js?v=3"></script>

<script src="https://cdn.plyr.io/3.6.2/plyr.js"></script>
<script>
    const player = new Plyr('#player');
</script>
@yield('js')
</body>

</html>
