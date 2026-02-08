/**
 * M-Pesa Deposit via Paystack
 * Handles M-Pesa payments through Paystack payment gateway
 */

var mpesaMinRecharge = 1; // Minimum set to 1 for testing

// Initialize on page load
$(document).ready(function() {
    var minRechargeInput = $('#mpesa_min_recharge');
    if (minRechargeInput.length) {
        mpesaMinRecharge = parseInt(minRechargeInput.val()) || 1;
    }
});

function showMpesaDeposit() {
    $('.deposite-box').hide();
    $('#mpesa').show();
    resetMpesaStatus();
}

function resetMpesaStatus() {
    $('#mpesa_status_box').hide();
    $('#mpesa_loading').hide();
    $('#mpesa_success').hide();
    $('#mpesa_error').hide();
    $('#mpesa_submit_btn').prop('disabled', false);
}

function initiateMpesaDeposit() {
    var phone = $('#mpesa_phone').val().trim();
    var amount = $('#mpesa_amount').val();
    var email = $('#mpesa_email').val().trim();
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Validation - phone must be 254XXXXXXXXX format
    if (!phone) {
        toastr.error('Please enter your M-Pesa phone number');
        return;
    }

    if (!phone.match(/^254[0-9]{9}$/)) {
        toastr.error('Phone number must be in format: 254XXXXXXXXX');
        return;
    }

    if (!email) {
        toastr.error('Please enter your email address');
        return;
    }

    if (!amount || parseInt(amount) < mpesaMinRecharge) {
        toastr.error('Minimum deposit amount is KSh ' + mpesaMinRecharge);
        return;
    }

    // Disable button and show loading
    $('#mpesa_submit_btn').prop('disabled', true);
    $('#mpesa_status_box').show();
    $('#mpesa_loading').show();
    $('#mpesa_success').hide();
    $('#mpesa_error').hide();

    // Initialize Paystack M-Pesa payment
    $.ajax({
        url: '/paystack/mpesa/initialize',
        method: 'POST',
        data: {
            _token: csrfToken,
            phone: phone,
            amount: amount,
            email: email
        },
        success: function(response) {
            if (response.isSuccess) {
                toastr.success('Redirecting to M-Pesa payment...');
                // Redirect to Paystack payment page
                setTimeout(function() {
                    window.location.href = response.authorization_url;
                }, 1000);
            } else {
                showMpesaError(response.message || 'Failed to initialize payment');
            }
        },
        error: function(xhr) {
            var errorMsg = 'Failed to initialize payment';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showMpesaError(errorMsg);
        }
    });
}

function showMpesaSuccess() {
    $('#mpesa_loading').hide();
    $('#mpesa_success').show();
    toastr.success('Payment successful! Your wallet has been credited.');
    
    // Update wallet balance immediately
    if (typeof updateWalletBalanceFromServer === 'function') {
        updateWalletBalanceFromServer();
        console.log('💰 Wallet balance updated after M-Pesa deposit');
    }
    
    setTimeout(function() {
        resetMpesaStatus();
        $('#mpesa_amount').val('');
        $('#mpesa_phone').val('');
    }, 2000);
}

function showMpesaError(message) {
    $('#mpesa_loading').hide();
    $('#mpesa_error').show();
    $('#mpesa_error_msg').text(message);
    $('#mpesa_submit_btn').prop('disabled', false);
    toastr.error(message);
}
