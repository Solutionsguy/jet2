/**
 * M-Pesa STK Push Deposit JavaScript
 * Handles M-Pesa Lipa Na M-Pesa deposit functionality
 */

var mpesaCheckoutRequestId = null;
var mpesaStatusCheckInterval = null;
var mpesaMinRecharge = 10; // Default, will be updated from page

// Initialize on page load
$(document).ready(function() {
    // Get min recharge from hidden input if available
    var minRechargeInput = $('#mpesa_min_recharge');
    if (minRechargeInput.length) {
        mpesaMinRecharge = parseInt(minRechargeInput.val()) || 10;
    }
});

function showMpesaDeposit() {
    // Hide all other deposit boxes
    $('.deposite-box').hide();
    // Show M-Pesa box
    $('#mpesa').show();
    // Reset status
    resetMpesaStatus();
}

function resetMpesaStatus() {
    $('#mpesa_status_box').hide();
    $('#mpesa_loading').hide();
    $('#mpesa_success').hide();
    $('#mpesa_error').hide();
    $('#mpesa_submit_btn').prop('disabled', false);
    if (mpesaStatusCheckInterval) {
        clearInterval(mpesaStatusCheckInterval);
        mpesaStatusCheckInterval = null;
    }
}

function initiateMpesaDeposit() {
    var phone = $('#mpesa_phone').val().trim();
    var amount = $('#mpesa_amount').val();
    var minAmount = mpesaMinRecharge;
    var csrfToken = $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content');

    // Validation
    if (!phone) {
        toastr.error('Please enter your M-Pesa phone number');
        return;
    }

    if (!amount || parseInt(amount) < minAmount) {
        toastr.error('Minimum deposit amount is ' + minAmount + ' KES');
        return;
    }

    // Validate phone format (basic validation)
    var phoneRegex = /^(07|01|2547|2541|\+2547|\+2541)[0-9]{8}$/;
    if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
        toastr.error('Please enter a valid M-Pesa phone number (e.g., 07XXXXXXXX)');
        return;
    }

    // Disable button and show loading
    $('#mpesa_submit_btn').prop('disabled', true);
    $('#mpesa_status_box').show();
    $('#mpesa_loading').show();
    $('#mpesa_success').hide();
    $('#mpesa_error').hide();

    // Send STK Push request
    $.ajax({
        url: '/mpesa/deposit',
        method: 'POST',
        data: {
            _token: csrfToken,
            phone: phone,
            amount: amount
        },
        success: function(response) {
            if (response.isSuccess) {
                toastr.success(response.message);
                mpesaCheckoutRequestId = response.checkout_request_id;
                
                // Start polling for payment status
                startMpesaStatusCheck();
            } else {
                showMpesaError(response.message || 'Failed to initiate M-Pesa payment');
            }
        },
        error: function(xhr) {
            var errorMsg = 'Failed to initiate M-Pesa payment';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showMpesaError(errorMsg);
        }
    });
}

function startMpesaStatusCheck() {
    var checkCount = 0;
    var maxChecks = 30; // Check for 60 seconds (every 2 seconds)
    var csrfToken = $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content');

    mpesaStatusCheckInterval = setInterval(function() {
        checkCount++;

        if (checkCount >= maxChecks) {
            clearInterval(mpesaStatusCheckInterval);
            showMpesaError('Payment timeout. Please check your M-Pesa messages and try again if needed.');
            return;
        }

        // Query payment status
        $.ajax({
            url: '/mpesa/status',
            method: 'POST',
            data: {
                _token: csrfToken,
                checkout_request_id: mpesaCheckoutRequestId
            },
            success: function(response) {
                if (response.isSuccess && response.status === 'completed') {
                    clearInterval(mpesaStatusCheckInterval);
                    showMpesaSuccess();
                } else if (response.status === 'cancelled' || response.status === 'failed') {
                    clearInterval(mpesaStatusCheckInterval);
                    showMpesaError(response.message || 'Payment was cancelled or failed');
                }
                // If still pending, continue polling
            },
            error: function() {
                // Continue polling on error (might be temporary)
                console.log('Status check failed, retrying...');
            }
        });
    }, 2000); // Check every 2 seconds
}

function showMpesaSuccess() {
    $('#mpesa_loading').hide();
    $('#mpesa_success').show();
    toastr.success('Payment successful! Your account has been credited.');
    
    // Redirect to deposit page after 2 seconds
    setTimeout(function() {
        window.location.href = '/deposit?msg=Success';
    }, 2000);
}

function showMpesaError(message) {
    $('#mpesa_loading').hide();
    $('#mpesa_error').show();
    $('#mpesa_error_msg').text(message);
    $('#mpesa_submit_btn').prop('disabled', false);
    toastr.error(message);
}
