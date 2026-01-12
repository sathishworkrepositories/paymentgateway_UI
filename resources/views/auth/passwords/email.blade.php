@php 
$title = "Reset Password";
@endphp
@extends('layouts.app')

@section('content')
<section class="sign-in-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6 col-xl-6 col-md-12 col-sm-12 col-xs-12 my-auto bg-img">
                    <div class="loginouterbox">
                        <div class="login-form">
                            <div class="logo">
                                <a href="{{ url('/') }}">
                                <img src="{{ url('img/sign-in-logo.png') }}" alt="" class="img-fluid">
                                </a>
                            </div>
                            
                         <h4 class="heading-title">Reset Password</h4>
                         <p class="para-title">Enter your email address and we’ll send you an email with instructions to reset your password</p>
                         <div class="formcontentbox">
                            <div class="mlmwizardform">
                                    @if (session('status'))
                                        <div class="alert alert-success" role="alert">
                                            {{ session('status') }}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('password.email') }}">
                                        @csrf 
                                    <fieldset>
                                        <div class="form-card">
                                            <div class="form-group ">
                                                <label>Email Address<span class="text text-danger">*</span></label>
                                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                                @error('email')
                                                    <span class="text-danger errors-text show_error_email" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror<br/>
                                            </div>
                                            <div class="text-center">
                                                    <div class="form-group g-recaptcha-whl">
                                                        {!! app('captcha')->display() !!}
                                                        @if ($errors->has('g-recaptcha-response'))
                                                        <span class="text-danger errors-text show_error_password">
                                                        <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                                        </span>
                                                        @endif
                                                    </div>
                                                    </div>
                                            <button class="btn next_l action-button yellowbtn">Reset</button>
                                        </div>
                                    </fieldset>
                                                                 
                                </form>
                            </div>
                        </div>
                        </div>
                     </div>
                </div>
                <div class="col-lg-6 col-xl-6 col-md-12 col-sm-12 col-xs-12 right-side">
                    <div class="signin-right-side-img">
                        <!-- <img src="{{url('img/confirm-pageright-img.png')}}" alt="" class="img-fluid"> -->
                    </div>
                </div>
                
            </div>
        </div>
    </section>
    <script src='https://www.google.com/recaptcha/api.js'></script>
@endsection
