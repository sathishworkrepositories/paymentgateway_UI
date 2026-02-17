@php $title = "Withdraw Conformation"; @endphp
@extends('layouts.app')
@section('content')
	<div class="pagecontent gridpagecontent innerpagegrid">


			</section>
			<article class="gridparentbox">
				<div class="container sitecontainer">
					<div class="row formboxbg">
						<div class="col-md-5 col-sm-12 col-12 mx-auto">
							<h2 class="heading-title text-center"><img src="{{ url('images/switch-exchange-logo-dark.svg') }}" alt="" style="height:100px"></h2>
							<h2 class="heading-title text-center">2FA OTP</h2>

							<div class="login-form">
								<div class="loginformbox">
								@if (session('faild'))
								<div class="alert alert-danger">
									{{ session('faild') }}
								</div>
								@endif
								@if (session('success'))
								<div class="alert alert-success">
									{{ session('success') }}
								</div>
								@endif
									<div class="formcontentbox">
							<form class="siteformbg"  method="post" action="{{ url('/validateotp') }}">
								{{ csrf_field() }}

								<div class="form-group cpybtnbg">
									<label >Enter 6 Digit Code</label>
										<input id="otp" type="number"  class="form-control" name="otp" value="{{ old('otp') }}" required autofocus>
									@if ($errors->has('otp'))
									<span class="help-block">
										<strong>{{ $errors->first('otp') }}</strong>
									</span>
									@endif
								</div>

							<div class="col-md-12 text-center">
							<button type="submit" class="blue-btn" >@lang('common.Submit')</button>
							</div>
						</form>
						</div>
								</div>
							</div>
					</div>
					</div>
				</div>
			</article>

</div>

<script>
$("body").addClass("loginbanner");
</script>
@endsection
