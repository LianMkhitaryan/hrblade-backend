@extends('landing.layouts.main')

@section('content')
    <section class="title__block">
        <div class="container">
            <h1 class="fs-35 fw-semibold font-montserrat mb-3 mb-lg-4">Features</h1>
            <div class="fs-18 color-gray2 fw-light  lh18 ">Simple yet powerful</div>
        </div>
    </section>
    <div class="features__1">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_1.png" alt="">
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Video interview with unlimited possibilities </div>
                    <div class="color-gray2 lh18 fw-light">Simple interface with powerful functionality. Get feedback from hundreds of candidates without wasting time.</div>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4 order-1 order-md-0">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Any device</div>
                    <div class="color-gray2 lh18  fw-light">HRBlade works on all platforms, it is convenient for employers and job seekers</div>
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_2.png" alt="">
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_3.png" alt="">
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Any time</div>
                    <div class="color-gray2 lh18  fw-light">Day or night, your interview is always available worldwide. Candidates can respond to your video interview when it suits them.</div>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4 order-1 order-md-0">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Easy start</div>
                    <div class="color-gray2 lh18  fw-light">It's easy to get started. Quick registration and interview creation in 5 minutes.</div>
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_4.png" alt="">
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_5.png" alt="">
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Secure</div>
                    <div class="color-gray2 lh18  fw-light">HRBlade uses the highest enterprise-grade security standards to protect your data.</div>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4 order-1 order-md-0">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">User experience</div>
                    <div class="color-gray2 lh18  fw-light">Users enjoy premium interviewing on any device.</div>
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_6.png" alt="">
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/img_7.png" alt="">
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Superior Support</div>
                    <div class="color-gray2 lh18  fw-light">Quality email support 24/7</div>
                </div>
            </div>
        </div>
    </div>
    <section class="app__container">
        <div class="container">
            <img src="/landing/img/app__container1.png" alt="">
            <div class="row">
                <div class="col-md-7">
                    <div class="fs-30 fw-semibold font-montserrat mb-3 mb-lg-4 color-white">Start now</div>
                    <div class="color-white mb-3 lh18">Interview is provided with an amazing video interview service. Get video answers to your questions from anyone, anytime.
                    </div>
                    <a href="{{route('page', 'register')}}" class="button-white2">Try Now!</a>
                </div>
            </div>
        </div>
    </section>
@endsection
