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

function initiateMpesaWithdraw() {
    var phone = $('#mpesa_withdraw_phone').val().trim();
    var amount = $('#mpesa_withdraw_amount').val();
    var walletBalance = parseFloat($('#mpesa_wallet_balance').val()) || 0;
    var minWithdraw = mpesaMinWithdraw;
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

    if (!amount || parseInt(amount) < minWithdraw) {
        toastr.error('Minimum withdrawal amount is KSh ' + minWithdraw);
        return;
    }

    if (parseInt(amount) > walletBalance) {
        toastr.error('Insufficient balance. Available: KSh ' + walletBalance.toFixed(2));
        return;
    }

    // Confirm withdrawal
    if (!confirm('Withdraw KSh ' + parseInt(amount).toLocaleString() + ' to M-Pesa number ' + phone + '?')) {
        return;
    }

    // Show loading
    $('#mpesa_withdraw_btn').prop('disabled', true);
    $('#mpesa_withdraw_status').show();
    $('#mpesa_withdraw_loading').show();
    $('#mpesa_withdraw_success').hide();
    $('#mpesa_withdraw_error').hide();

    // Send Paystack M-Pesa withdrawal request
    $.ajax({
        url: '/paystack/mpesa/withdraw',
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

// Alias for backward compatibility
function initiateMpesaWithdrawal() {
    initiateMpesaWithdraw();
}

function showMpesaWithdrawSuccess(message) {
    $('#mpesa_withdraw_loading').hide();
    $('#mpesa_withdraw_success').show();
    toastr.success(message || 'Withdrawal initiated successfully!');
    
    // Update wallet balance immediately
    if (typeof updateWalletBalanceFromServer === 'function') {
        updateWalletBalanceFromServer();
        console.log('💰 Wallet balance updated after M-Pesa withdrawal');
    }
    
    // Close modal and stay on same page after 2 seconds
    setTimeout(function() {
        // Hide withdraw form/modal if exists
        if ($('#mpesa_withdraw_modal').length) {
            $('#mpesa_withdraw_modal').modal('hide');
        }
        resetMpesaWithdrawStatus();
        // Clear form
        $('#mpesa_withdraw_amount').val('');
        $('#mpesa_withdraw_phone').val('');
    }, 2000);
}

function showMpesaWithdrawError(message) {
    $('#mpesa_withdraw_loading').hide();
    $('#mpesa_withdraw_error').show();
    $('#mpesa_withdraw_error_msg').text(message);
    $('#mpesa_withdraw_btn').prop('disabled', false);
    toastr.error(message);
}
