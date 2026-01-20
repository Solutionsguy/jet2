<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{env('APP_NAME')}} - Login Panel</title>
    
    <link rel="stylesheet" href="{{ asset('aviatoradmin/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('aviatoradmin/assets/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('aviatoradmin/assets/css/style.css') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/izitoast/css/iziToast.min.css') }}">
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row flex-grow">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left p-5">
                <div class="brand-logo">
                  <img src="{{ asset('images/logo.png') }}">
                </div>
                <h4>Hello! let's get started</h4>
                <h6 class="font-weight-light">Sign in to continue.</h6>
                <form class="pt-3" id="loginadmin">
                    @csrf
                  <div class="form-group">
                    <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Username" required>
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required>
                  </div>
                  <div class="mt-3">
                    <button class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn" type="submit">SIGN IN</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery-3.6.1.min.js') }}"></script>
    <script src="{{ asset('aviatoradmin/assets/vendors/js/vendor.bundle.base.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="{{ asset('vendor/jquery-validation/dist/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('vendor/izitoast/js/iziToast.min.js') }}"></script>

   {{-- <script src="{{ asset('aviatoradmin/assets/js/off-canvas.js') }}"></script> --}}
{{-- <script src="{{ asset('aviatoradmin/assets/js/hoverable-collapse.js') }}"></script> --}}
{{-- <script src="{{ asset('aviatoradmin/assets/js/misc.js') }}"></script> --}}
    <script src="{{ asset('js/appcustomize.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Prevent default form submission
            $("#loginadmin").on('submit', function(e) {
                e.preventDefault();
            });

            // Initialize validation
            if ($.isFunction($.fn.validate)) {
                $("#loginadmin").validate({
                    submitHandler: function(form) {
                        apex("POST", "{{ url('auth/admin/login') }}", new FormData(form), form, "/admin/dashboard", "#");
                    }
                });
            } else {
                console.error("Validation plugin failed to load!");
            }
        });
    </script>
  </body>
</html>