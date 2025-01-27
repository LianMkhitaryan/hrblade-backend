@extends('landing.layouts.main')

@section('content')

    <section class="title__block">
        <div class="container">
            <h1 class="fs-35 fw-semibold font-montserrat mb-3 mb-lg-4">About HRBlade</h1>
            <div class="fs-18 color-gray2 fw-light  lh18">Let's cut to the chase, we offer delightfully simple asynchronous video interviewing</div>
        </div>
    </section>
    <section class="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 tar  mb-3 mb-lg-5">
                    <img src="/landing/img/about1.png" alt="" class=" mb-3  d-md-block ml-auto">
                    <img src="/landing/img/about2.png" alt="" class="ml-auto d-block d-md-inline ">
                </div>
                <div class="col-md-6 pl-md-5 mb-3 mb-lg-5">
                    <div class="fs-30 font-montserrat fw-semibold mb-3 mb-lg-4">Receive video responses</div>
                    <div class="color-gray lh18 mb-3 mb-lg-5">Receive video responses to your questions remotely from anyone, anywhere in the world. </div>
                    <ul class="about__list">
                        <li><span class="fw-semibold color-black">Always Affordable</span> - We believe that video communication should benefit everyone</li>
                        <li><span class="fw-semibold color-black">User Experience</span> - Available wherever people are in the world. It works beautifully across all devices, every time</li>
                        <li><span class="fw-semibold color-black">Integrated</span> - Easily connect to over 2,000 of your favourite apps and automate your workflow</li>
                    </ul>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-lg-4 order-1 order-md-0">
                    <div class="color-gray2 fw-light  lh18 mb-4 mb-lg-5">1000's of organisations already use Willo to communicate <br> with more people, in less time, and never have to worry about <br> scheduling calls or meetings again.</div>
                    <div class="fs-27 lh18 fw-semibold">It is Free to get started and <br>
                        we have no setup fees or contracts</div>
                </div>
                <div class="col-md-6 mb-3 mb-lg-4 tac">
                    <img src="/landing/img/about3.png" alt="">
                </div>
            </div>
        </div>
    </section>
    <section class="app__container">
        <div class="container">
            <img src="/landing/img/app__container1.png" alt="">
            <div class="row">
                <div class="col-md-7">
                    <div class="fs-30 fw-semibold font-montserrat mb-3 mb-lg-4 color-white">Start using App</div>
                    <div class="color-white mb-3 lh18">Let's cut to the chase, we offer delightfully simple asynchronous video interviewing. Receive video responses to your questions remotely from anyone, anywhere in the world.</div>
                    <div class="color-white mb-4 mb-lg-5 lh18">It is Free to get started and we have no setup fees or contracts.</div>
                    <a href="{{route('page', 'register')}}" class="button-white2">Try Now!</a>
                </div>
            </div>
        </div>
    </section>
@endsection
