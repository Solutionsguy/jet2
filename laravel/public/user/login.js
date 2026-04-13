// $(".header-bottom").hide();
// $(".main-container").css("margin-top", "60px");
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
function onChangeCallback(ctr){
    var country = $("#countries").val(ctr);
    if (ctr == 'IN') {
        $("#currency option").removeAttr('selected').filter('[value=1]').attr('selected', true);
        $(".styledSelect").text('KES');
    } else {
        $("#currency option").removeAttr('selected').filter('[value=2]').attr('selected', true);
        $(".styledSelect").text('USD');
    }
}

$(document).ready(function () {
    $("#otp_div").hide();
    $("#otp_error").hide();
    $("#registerError").hide();
    $("#confirm_password-error").hide();
    $("#new_password-error").hide();

    const promocode = $("#referral_code").val();
    if (promocode != '' && promocode != undefined) {
        $('#register-modal').modal('show');
        $("#promocode").val(promocode);
        $("#promo_code").val(promocode);
        
    } 
})

$("#login").on('click', function() {
    $("#username").val('');
    $("#password").val('');
    $("#login-error").hide();
    $("#username-error").hide();
    $("#password-error").hide();

})

function login_ajax(logindata, redirect_url) {
    $.ajax({
        url: '/auth/login',
        data: logindata,
        type: "POST",
        dataType: "json",
        success: function(result) {
            $("#loginSubmit").prop('disabled', false);
            if (result.isSuccess) {
                window.location.href = redirect_url;
            } else {
                $("#login-error").text(result.message).fadeIn();
            }
        },
        error: function(xhr, status, error) {
            $("#loginSubmit").prop('disabled', false);
            $("#login-error").text('Connection error. Please try again.').fadeIn();
        }
    });
}


$('#loginForm').validate({
    errorPlacement: function(error, element) {
        // Validation errors are handled by bootstrap/custom styles but we can refine placement
        error.insertAfter(element.closest('.auth-input-wrapper'));
    },
    rules: {
        username: {
            required: true
        },
        password: {
            required: true
        }
    },
    messages: {
        username: {
            required: "Please enter your email or mobile",
        },
        password: {
            required: "Please enter your password",
        }
    },
    submitHandler: function(form) {
        $("#loginSubmit").prop('disabled', true);
        $("#login-error").hide();
        login_ajax($(form).serialize(), "/crash");
    }
});

$( "#forgotPassword" ).on('click', function(){
    $("#processSubmit").prop('disabled', false);
    $("#otp_error").hide();
    $("#otp").val('');
    $("#otp").prop('disabled', false);
});

$("#forgotPasswordForm").on('submit', function(e) {
    e.preventDefault(); 
    $("#processSubmit").prop('disabled', true);
    $.ajax({
        url: '/laravel/public/forgot_password_post',
        data: $(this).serialize(),
        type: "POST",
        dataType: "json",
        success: function(result) {
            $("#processSubmit").prop('disabled', false);
            if (result.isSuccess) {
                $("#user_name_div").hide();
                $("#otp_div").show();
                $("#otp_id").val(result.data.id);
            } else {
                $("#otp_error").text(result.message).fadeIn();
            }
        }
    });
})

$("#otp").on('input', function() {
    var otp = $(this).val();
    var otp_id = $("#otp_id").val();
    var username = $("#user_name").val();
    if(otp.length == 4) {
        $(this).prop('disabled', true);
        $.ajax({
            url  : '/laravel/public/verify_otp',
            type : 'post',
            data :  {
                'otp' : otp,
                'otp_id' : otp_id,
                'username' : username,
            },
            success : function(result) {
                if(result.isSuccess) {
                    $('#forgot-modal').modal('hide');
                    $('#reset-password-modal').modal('show');
                    $("#reset_username").val(result.data.username);
                    $("#otp_error").hide();
                } else {
                    $("#otp").prop('disabled', false);
                    $("#otp_error").text(result.message).fadeIn();
                }
            }
        })
    }
})

$('#registerViaEmailForm').validate({
    errorPlacement: function(error, element) {
        if (element.attr("type") == "checkbox") {
            error.insertAfter(element.parent());
        } else {
            error.insertAfter(element.closest('.auth-input-wrapper'));
        }
    },
    rules: {
        name: { required: true },
        mobile: { required: true },
        email: { required: true, email: true },
        password: { required: true, minlength: 6 }
    },
    submitHandler: function(form) {
        $("#register_via_email").prop('disabled', true);
        $.ajax({
            url: $(form).attr('action'),
            data: $(form).serialize(),
            type: "POST",
            dataType: "json",
            success: function(result) {
                $("#register_via_email").prop('disabled', false);
                if(result.isSuccess) {
                    $('#register-modal').modal('hide');
                    const data = {
                        _token: result.data.token,
                        username : result.data.username,
                        password : result.data.password,
                    }
                    login_ajax(data,'/crash')
                } else {
                    notify('error', result.message);
                }
            }
        });
    }
});

$(".reg_btn").on('click', function() {
    $("#reg_email").val('');
    $("#regpassword").val('');
    $("#promo_code").val('');
})

$("#email_policy").click(function() {
    $("#register_via_email").prop('disabled', !$(this).is(":checked"));
}); 

/*------- Password Visibility Toggles -------*/

$(document).ready(function() {
    // These are now handled in the blade template globally but keeping logic here if needed
});

