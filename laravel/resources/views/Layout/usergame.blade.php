<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Betting company {{ env('APP_NAME') }} - online sports betting</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/jquery.mCustomScrollbar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pretty-checkbox.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/niceCountryInput.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery.ccpicker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.dataTables.min.css') }}">

    @yield('css')
</head>

<body class="dark-bg-main">

@include('include.header')
@yield('content')
@include('include.footer')

<input type="hidden" id="referral_code" value="">

{{-- ?? Modals unchanged (HTML is OK) --}}

<!-- JS -->
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jquery.mCustomScrollbar.js') }}"></script>
<script src="{{ asset('js/niceCountryInput.js') }}"></script>
<script src="{{ asset('js/jquery.ccpicker.js') }}"></script>
<script src="{{ asset('js/anime.min.js') }}"></script>
<script src="{{ asset('js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('js/toastr.min.js') }}"></script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/dataTables.responsive.min.js') }}"></script>

<!-- SweetAlert -->
<script src="https://unpkg.com/sweetalert@2.1.2/dist/sweetalert.min.js"></script>

<!-- Global APP URL -->
<script>
    window.APP_URL = "{{ url('/') }}";
</script>

<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
});

$.ajax({
    url: window.APP_URL + '/get_user_details',
    type: 'GET',
    success: function (result) {
        if (result.isSuccess) {
            $("#avatar_img").attr('src', result.data.avatar);
            $("#username").text(result.data.username);

            if (result.data.notification) {
                swal('Notification', result.data.notification, 'success')
                    .then(() => {
                        $.post(window.APP_URL + '/update_is_notify', {
                            id: result.data.id,
                            request_type: result.data.request_type
                        });
                    });
            }
        }
    }
});
</script>

<!-- Login logic -->
<script src="{{ asset('user/login.js') }}"></script>

@yield('js')
</body>
</html>
