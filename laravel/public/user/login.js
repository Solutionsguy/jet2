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
    $("#otp_error").hide();

    $.ajax({
        url: '/forgot_password_post',
        data: $(this).serialize(),
        type: "POST",
        dataType: "json",
        success: function(result) {
            $("#processSubmit").prop('disabled', false);
            if (result.isSuccess) {
                $("#user_name_div").hide();
                $("#otp_div").show();
                $("#reset_email").val(result.data.email);
                notify('success', result.message);
            } else {
                $("#otp_error").text(result.message).fadeIn();
            }
        },
        error: function() {
            $("#processSubmit").prop('disabled', false);
            notify('error', 'Failed to request reset. Try again.');
        }
    });
})

$("#otp").on('input', function() {
    var otp = $(this).val();
    var email = $("#reset_email").val();
    if(otp.length == 6) {
        $(this).prop('disabled', true);
        $.ajax({
            url  : '/verify_otp',
            type : 'post',
            data :  {
                'otp' : otp,
                'email' : email
            },
            success : function(result) {
                if(result.isSuccess) {
                    $('#forgot-modal').modal('hide');
                    $('#reset-password-modal').modal('show');
                    $("#reset_otp").val(otp);
                    $("#otp_error").hide();
                } else {
                    $("#otp").prop('disabled', false);
                    $("#otp_error").text(result.message).fadeIn();
                }
            }
        })
    }
})

$('#resetPasswordForm').on('submit', function(e) {
    e.preventDefault();
    $("#resetSubmit").prop('disabled', true);
    $("#reset_error").hide();

    $.ajax({
        url: '/reset_password_post',
        data: $(this).serialize(),
        type: "POST",
        dataType: "json",
        success: function(result) {
            $("#resetSubmit").prop('disabled', false);
            if (result.isSuccess) {
                $('#reset-password-modal').modal('hide');
                notify('success', result.message);
                setTimeout(() => { $('#login-modal').modal('show'); }, 1500);
            } else {
                $("#reset_error").text(result.message).fadeIn();
            }
        },
        error: function() {
            $("#resetSubmit").prop('disabled', false);
            notify('error', 'Failed to reset password.');
        }
    });
});

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
        // Clear previous errors
        $('.server-error').remove();
        $('.auth-input-field').removeClass('is-invalid');

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
                    if (result.errors) {
                        // Show specific errors under each field
                        $.each(result.errors, function(field, messages) {
                            let input = $('[name="' + field + '"]');
                            input.addClass('is-invalid');
                            input.closest('.auth-input-wrapper').after('<div class="server-error text-danger small mt-1" style="font-size: 11px;">' + messages[0] + '</div>');
                        });
                    } else {
                        notify('error', result.message);
                    }
                }
            },
            error: function() {
                $("#register_via_email").prop('disabled', false);
                notify('error', 'Something went wrong. Please try again.');
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

