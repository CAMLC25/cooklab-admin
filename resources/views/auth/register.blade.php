@extends('layouts.app')

@section('content')
<div class="container-scroller">
  <div class="container-fluid page-body-wrapper full-page-wrapper">
    <div class="content-wrapper d-flex align-items-center auth px-0">
      <div class="row w-100 mx-0">
        <div class="col-lg-4 mx-auto">
          <div class="auth-form-light text-left py-5 px-4 px-sm-5">
            <div class="brand-logo text-center mb-4">
              <img src="{{ asset('admin-assets/images/cook_panel.png') }}" alt="logo">
            </div>
            <h4>New here?</h4>
            <h6 class="font-weight-light">Signing up is easy. It only takes a few steps</h6>

            <form class="pt-3" method="POST" action="{{ route('register') }}">
              @csrf

              <div class="form-group">
                <input type="text"
                       class="form-control form-control-lg @error('name') is-invalid @enderror"
                       id="name"
                       name="name"
                       placeholder="Username"
                       value="{{ old('name') }}"
                       required autofocus>
                @error('name')
                  <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                @enderror
              </div>

              <div class="form-group">
                <input type="email"
                       class="form-control form-control-lg @error('email') is-invalid @enderror"
                       id="email"
                       name="email"
                       placeholder="Email"
                       value="{{ old('email') }}"
                       required>
                @error('email')
                  <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                @enderror
              </div>

              {{-- <div class="form-group">
                <select class="form-control form-control-lg" name="country" id="country">
                  <option value="">-- Select Country --</option>
                  <option value="USA">United States of America</option>
                  <option value="UK">United Kingdom</option>
                  <option value="India">India</option>
                  <option value="Germany">Germany</option>
                  <option value="Argentina">Argentina</option>
                </select>
              </div> --}}

              <div class="form-group">
                <input type="password"
                       class="form-control form-control-lg @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       placeholder="Password"
                       required>
                @error('password')
                  <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                @enderror
              </div>

              <div class="form-group">
                <input type="password"
                       class="form-control form-control-lg"
                       id="password-confirm"
                       name="password_confirmation"
                       placeholder="Confirm Password"
                       required>
              </div>

              <div class="mb-4">
                <div class="form-check">
                  <label class="form-check-label text-muted">
                    <input type="checkbox" class="form-check-input" required>
                    I agree to all Terms & Conditions
                  </label>
                </div>
              </div>

              <div class="mt-3">
                <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                  SIGN UP
                </button>
              </div>

              <div class="text-center mt-4 font-weight-light">
                Already have an account?
                <a href="{{ route('login') }}" class="text-primary">Login</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
