@extends('landing.layouts.main')

@section('content')
    <div class="form__container">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-5 mb-md-0 ">
                    <div class="fs-35 fw-semibold font-montserrat mb-3  ">Subscription</div>
                    <div class="fs-20 mb-2 mb-lg-2 pb-3">You will be charged ${{ number_format($plan->price, 2) }} for <span class="color-blue fw-semibold">{{ $plan->name }}</span> Plan</div>
                    @if($free->id != $plan->id)
                        <div class="fs-20">   Enter your credit card information<br>
                        </div>
                    @endif

                    <img src="/landing/img/signup.png" alt="" class="form__container--img">
                </div>
                <div class="col-md-6 col-lg-5 offset-lg-1 mb-4 mb-md-0 tac">
                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if(auth()->user()->agency->subscribedToPlan($pro->stripe_price, $pro->stripe_name) || auth()->user()->agency->subscribedToPlan($starter->stripe_price, $starter->stripe_name))
                        @if($free->id == $plan->id)
                                <form action="{{ route('subscription.cancel') }}" method="post" id="payment-form">
                                    @csrf
                                    <button class="button-blue">Cancel subscription</button>
                                </form>
                        @else
                                <form action="{{ route('subscription.swap') }}" method="post" id="payment-form">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $plan->id }}" />
                                    <button class="button-blue">Swap subscription</button>
                                </form>
                        @endif
                    @else
                            <form action="{{ route('subscription.create') }}" method="post" id="payment-form">
                                @csrf
                                <input type="text" name="name" id="card-holder-name" class="form-control" value="" placeholder="Name on the card">
                                <div id="card-element">
                                    <!-- A Stripe Element will be inserted here. -->
                                </div>
                                <!-- Used to display form errors. -->
                                <div id="card-errors" role="alert"></div>
                                <input type="hidden" name="plan" value="{{ $plan->id }}" />
                                <button class="button-blue" id="card-button" data-secret="{{ $intent->client_secret }}">Subscribe</button>
                            </form>
                    @endif
                    <img src="/landing/img/signup2.png" alt="" class="d-block mx-auto d-md-none">
                </div>
            </div>
        </div>
    </div>
@endsection



@section('js')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Create a Stripe client.
        var stripe = Stripe('pk_test_51HoQ6jFuKk6f4NOIjPbT5hQUZrIPdMhxbWaPlMj3BMm4bRdMtcKvd4uN5sec5DH6kzwcNBkXv7rDf48BF5JD8Kin002ByPXzYt');
        // Create an instance of Elements.
        const elements = stripe.elements()
        const cardElement = elements.create('card')

        cardElement.mount('#card-element')

        const form = document.getElementById('payment-form')
        const cardBtn = document.getElementById('card-button')
        const cardHolderName = document.getElementById('card-holder-name')

        form.addEventListener('submit', async (e) => {
            e.preventDefault()

            cardBtn.disabled = true;
            const { setupIntent, error } = await stripe.confirmCardSetup(
                cardBtn.dataset.secret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: cardHolderName.value
                        }
                    }
                }
            )

            if(error) {
                cardBtn.disable = false
            } else {
                let token = document.createElement('input')

                token.setAttribute('type', 'hidden')
                token.setAttribute('name', 'token')
                token.setAttribute('value', setupIntent.payment_method)

                form.appendChild(token)

                form.submit();
            }
        })
    </script>
@endsection