/**
 * M-Pesa B2C Withdrawal JavaScript
 * Handles M-Pesa instant withdrawal functionality
 */

var mpesaB2CAvailable = false;
var mpesaMinWithdraw = 100; // Default, will be updated from page

// Check if M-Pesa B2C is available on page load
$(document).ready(function() {
    // Get min withdraw from hidden input if available
    var minWithdrawInput = $('#mpesa_min_withdraw');
    if (minWithdrawInput.length) {
        mpesaMinWithdraw = parseInt(minWithdrawInput.val()) || 100;
    }
    checkMpesaB2CAvailability();
});

function checkMpesaB2CAvailability() {
    $.ajax({
        url: '/mpesa/b2c-available',
        method: 'GET',
        success: function(response) {
            mpesaB2CAvailable = response.b2c_available;
            if (!mpesaB2CAvailable) {
                console.log('M-Pesa B2C not configured');
            }
        },
        error: function() {
            mpesaB2CAvailable = false;
        }
    });
}

function showMpesaWithdraw() {
    resetMpesaWithdrawStatus();
    $('#mpesa_withdraw_amount').val('');
}

function resetMpesaWithdrawStatus() {
    $('#mpesa_withdraw_status').hide();
    $('#mpesa_withdraw_loading').hide();
    $('#mpesa_withdraw_success').hide();
    $('#mpesa_withdraw_error').hide();
    $('#mpesa_withdraw_btn').prop('disabled', false);
}

function initiateMpesaWithdrawal() {
    var phone = $('#mpesa_withdraw_phone').val().trim();
    var amount = $('#mpesa_withdraw_amount').val();
    var walletBalance = parseFloat($('#mpesa_wallet_balance').val()) || 0;
    var minWithdraw = mpesaMinWithdraw;
    var csrfToken = $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content');

    // Validation
    if (!phone) {
        toastr.error('Please enter your M-Pesa phone number');
        return;
    }

    // Validate phone format
    var phoneRegex = /^(07|01|2547|2541|\+2547|\+2541)[0-9]{8}$/;
    if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
        toastr.error('Please enter a valid M-Pesa phone number (e.g., 07XXXXXXXX)');
        return;
    }

    if (!amount || parseInt(amount) < minWithdraw) {
        toastr.error('Minimum withdrawal amount is KES ' + minWithdraw);
        return;
    }

    if (parseInt(amount) > walletBalance) {
        toastr.error('Insufficient balance. Available: KES ' + walletBalance.toFixed(2));
        return;
    }

    // Confirm withdrawal
    if (!confirm('Withdraw KES ' + parseInt(amount).toLocaleString() + ' to M-Pesa number ' + phone + '?')) {
        return;
    }

    // Show loading
    $('#mpesa_withdraw_btn').prop('disabled', true);
    $('#mpesa_withdraw_status').show();
    $('#mpesa_withdraw_loading').show();
    $('#mpesa_withdraw_success').hide();
    $('#mpesa_withdraw_error').hide();

    // Send withdrawal request
    $.ajax({
        url: '/mpesa/withdraw',
        method: 'POST',
        data: {
            _token: csrfToken,
            phone: phone,
            amount: amount
        },
        success: function(response) {
            if (response.isSuccess) {
                showMpesaWithdrawSuccess(response.message);
            } else {
                showMpesaWithdrawError(response.message || 'Withdrawal failed');
            }
        },
        error: function(xhr) {
            var errorMsg = 'Failed to process withdrawal';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showMpesaWithdrawError(errorMsg);
        }
    });
}

function showMpesaWithdrawSuccess(message) {
    $('#mpesa_withdraw_loading').hide();
    $('#mpesa_withdraw_success').show();
    toastr.success(message || 'Withdrawal initiated successfully!');
    
    // Redirect after 3 seconds
    setTimeout(function() {
        window.location.href = '/withdraw?msg=Success';
    }, 3000);
}

function showMpesaWithdrawError(message) {
    $('#mpesa_withdraw_loading').hide();
    $('#mpesa_withdraw_error').show();
    $('#mpesa_withdraw_error_msg').text(message);
    $('#mpesa_withdraw_btn').prop('disabled', false);
    toastr.error(message);
}
