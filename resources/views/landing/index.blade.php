@extends('landing.layouts.main')

@section('content')
    <section class="main__1">
        <div class="container">
            <div class="row">
                <div class="col-md-6 d-flex flex-column justify-content-between align-items-start mb-4 mb-md-0 ">
                    <h1 class="main__1--title fw-semibold font-montserrat mb-4 mb-lg-5"><span class="color-white relative main__1--first">S</span>imple video interview service with Enterprise features</h1>
                    <div class="color-gray2 lh18 mb-4 mb-xl-5 pb-2 pb-lg-5">Interview is ideal for individuals, groups and departments - our power users save time, hassle and money by finding the best people.</div>
                    <a href="{{route('page', 'register')}}" class="button-blue2">Try Now!</a>
                </div>
                <div class="col-md-6 ">
                    <video id="player" controls data-poster="/landing/img/poster.png">
                        <source src="video.mp4" type="video/mp4" />
                        <source src="/path/to/video.webm" type="video/webm" />
                        <!-- Captions are optional -->
{{--                        <track kind="captions" label="English captions" src="/path/to/captions.vtt" srclang="en" default />--}}
                    </video>
                </div>
            </div>
        </div>
    </section>
    <section class="main__2">
        <div class="container tac">
            <h2 class="fs-30 font-montserrat fw-semibold mb-4">Why choose us?</h2>
            <div class="line-blue80 d-inline-block"></div>
            <div class="row align-items-center my-4 my-lg-5 py-4 py-lg-5">
                <div class="col-md-4 mb-3 order-1 order-md-0">
                    <div class="fs-20 color-blue font-montserrat fw-semibold mb-4">Premium experience</div>
                    <div class="color-gray2 lh18">Interwoo designed around the best user experience</div>
                </div>
                <div class="col-md-4 mb-3">
                    <img src="/landing/img/main__2.png" alt="">
                </div>
                <div class="col-md-4 mb-0 order-1 order-md-0">
                    <div class="fs-20 color-blue font-montserrat fw-semibold mb-4">Affordable</div>
                    <div class="color-gray2 lh18">Perfect price for companies of all sizes</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mx-auto pt-0 pt-lg-5">
                <div class="fs-20 color-blue font-montserrat fw-semibold mb-4">Any Device</div>
                <div class="color-gray2 lh18">Available on any device. Access anytime and anywhere</div>
            </div>
        </div>
    </section>
    <section class="main__3">
        <div class="container">
            <h3 class="fs-30 tac font-montserrat fw-semibold mb-4 mb-lg-5">How it works</h3>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="row align-items-center">
                        <div class="col-md mb-3 mb-sm-4 mb-md-0">
                            <div class="fs-50 color-blue font-montserrat fw-semibold">1</div>
                            <div class="fs-20 font-montserrat fw-semibold">Quick Start</div>
                            <div class="line-blue80 d-inline-block my-3"></div>
                            <div class="color-gray2 lh18">Create your first HRBlade in 5 minutes</div>
                        </div>
                        <div class="col-md-3">
                            <a href="">
                                <img src="/landing/img/icon-play.svg" alt="">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="row align-items-center">
                        <div class="col-md mb-3 mb-sm-4 mb-md-0">
                            <div class="fs-50 color-blue font-montserrat fw-semibold">2</div>
                            <div class="fs-20 font-montserrat fw-semibold">Invite people</div>
                            <div class="line-blue80 d-inline-block my-3"></div>
                            <div class="color-gray2 lh18">Easy and quick people invitation to record video responses</div>
                        </div>
                        <div class="col-md-3">
                            <a href="">
                                <img src="/landing/img/icon-play.svg" alt="">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="row align-items-center">
                        <div class="col-md mb-3 mb-sm-4 mb-md-0">
                            <div class="fs-50 color-blue font-montserrat fw-semibold">3</div>
                            <div class="fs-20 font-montserrat fw-semibold">Flexible communication</div>
                            <div class="line-blue80 my-3"></div>
                            <div class="color-gray2 lh18">People respond to your questions in their own time</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="main__4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4">
                    <img src="/landing/img/main4.png" alt="">
                </div>
                <div class="col-md-6 mb-3 mb-lg-4">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">All interviews in one place</div>
                    <div class="color-gray2 lh18 fw-light">Easily see all your video responses on one dashboard. Secure in the cloud. Keep everyone on the same page with star ratings and comments. Track all your video responses and monitor key metrics like conversion.</div>
                </div>
            </div>
        </div>
    </section>
    <section class="app__container">
        <div class="container">
            <img src="/landing/img/app__container1.png" alt="">
            <div class="row">
                <div class="col-md-7">
                    <div class="fs-30 fw-semibold font-montserrat mb-3 mb-lg-4 color-white">Start now</div>
                    <div class="color-white mb-3 lh18">Let's cut to the chase, we offer delightfully simple asynchronous video interviewing. Receive video responses to your questions remotely from anyone, anywhere in the world.</div>
                    <a href="{{route('page', 'register')}}" class="button-white2">Try it Now!</a>
                </div>
            </div>
        </div>
    </section>
@endsection
