@extends('landing.layouts.main')

@section('content')
    @if (session('status'))
        <div class="success-status">
            {{ session('status') }}
        </div>
    @endif
    <section class="title__block">
        <div class="container">
            <h1 class="fs-35 fw-semibold font-montserrat mb-3 mb-lg-4">Pricing</h1>
            <div class="fs-18 color-gray2 fw-light  lh18">Find the plan that's right for you. <br>
                Start for free, or pay monthly. No contracts.</div>
        </div>
    </section>
    <div class="pricing">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 pr-md-0 mb-3 mb-md-0">
                    <div class="pricing__container">
                        <div class="fs-30 font-montserrat fw-semibold mb-4 mb-lg-5 tac">{{$free->name}}</div>
                        <div class="fs-30 color-gray tac mb-4 mb-lg-5">$<span class="fs-80 color-black lh1 font-montserrat fw-semibold">0</span> <span class="vat">/mo</span> </div>
                        <div class="fs-semibold tac color-gray mb-4 mb-lg-5">{{$free->description}}</div>
                        <ul class="pricing__list mb-5">
                            @foreach($free->bonuses as $bonus)
                                <li>{{$bonus}}</li>
                            @endforeach
                        </ul>
                        @if(\Illuminate\Support\Facades\Auth::user())
                            @if(auth()->user()->agency->subscribedToPlan($pro->stripe_price, $pro->stripe_name) || auth()->user()->agency->subscribedToPlan($starter->stripe_price, $starter->stripe_name))
                                <a href="{{route('plan.get', $free->id)}}" class="button-blue-light">Cancel subscription</a>
                            @else
                                <div class="fs-semibold tac color-gray mb-4 mb-lg-5">Your plan</div>
                            @endif
                        @else
                            <a href="{{route('register')}}" class="button-blue-light">Create Account</a>
                        @endif

                    </div>
                </div>
                <div class="col-md-4 px-md-0 mb-3 mb-md-0 order-1 order-md-0">
                    <div class="pricing__container">
                        <div class="fs-30 font-montserrat fw-semibold mb-4 mb-lg-5 tac">{{$pro->name}}</div>
                        <div class="fs-30 color-gray tac mb-4 mb-lg-5">from $<span class="fs-80 color-black lh1 font-montserrat fw-semibold">{{$pro->price}}</span> <span class="vat">/mo</span> </div>
                        <div class="fs-semibold tac color-gray mb-4 mb-lg-5">{{$pro->description}}</div>
                        <ul class="pricing__list mb-5">
                            @foreach($pro->bonuses as $bonus)
                                <li>{{$bonus}}</li>
                            @endforeach
                        </ul>
                        @if(\Illuminate\Support\Facades\Auth::user())
                            @if(auth()->user()->agency->subscribedToPlan($pro->stripe_price, $pro->stripe_name))
                                <div class="fs-semibold tac color-gray mb-4 mb-lg-5">Your plan</div>
                            @else
                                <a href="{{route('plan.get', $pro->id)}}" class="button-blue">Subscribe</a>
                            @endif
                        @else
                            <a href="{{route('register')}}" class="button-blue">Create Account</a>
                        @endif

                    </div>
                </div>
                <div class="col-md-4 pl-md-0 mb-3 mb-md-0">
                    <div class="pricing__container">
                        <div class="fs-30 font-montserrat fw-semibold mb-4 mb-lg-5 tac">{{$starter->name}}</div>
                        <div class="fs-30 color-gray tac mb-4 mb-lg-5">$<span class="fs-80 color-black lh1 font-montserrat fw-semibold">{{$starter->price}}</span> <span class="vat">/mo</span> </div>
                        <div class="fs-semibold tac color-gray mb-4 mb-lg-5">{{$starter->description}}</div>
                        <ul class="pricing__list mb-5">
                            @foreach($starter->bonuses as $bonus)
                                <li>{{$bonus}}</li>
                            @endforeach

                        </ul>
                        @if(\Illuminate\Support\Facades\Auth::user())
                            @if(auth()->user()->agency->subscribedToPlan($starter->stripe_price, $starter->stripe_name))
                                <div class="fs-semibold tac color-gray mb-4 mb-lg-5">Your plan</div>
                            @else
                                <a href="{{route('plan.get', $starter->id)}}" class="button-blue-light">Subscribe</a>
                            @endif
                        @else
                            <a href="{{route('register')}}" class="button-blue-light">Create Account</a>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
