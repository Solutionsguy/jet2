/**
 * Paystack Deposit JavaScript
 * Handles Paystack payment integration for deposits
 */

var paystackPublicKey = null;
var paystackMinDeposit = 100;
var paystackCurrency = 'NGN';
var paystackCurrencySymbol = '₦';

// Initialize Paystack on page load
$(document).ready(function() {
    loadPaystackConfig();
});

/**
 * Load Paystack configuration
 */
function loadPaystackConfig() {
    $.ajax({
        url: '/paystack/config',
        method: 'GET',
        success: function(response) {
            paystackPublicKey = response.public_key;
            paystackCurrency = response.currency || 'NGN';
            paystackCurrencySymbol = response.currency_symbol || '₦';
            console.log('✅ Paystack configured:', paystackCurrency);
        },
        error: function() {
            console.error('❌ Failed to load Paystack config');
        }
    });
    
    // Load min deposit
    $.ajax({
        url: '/paystack/availability',
        method: 'GET',
        success: function(response) {
            if (response.available) {
                paystackMinDeposit = response.min_deposit || 100;
                console.log('✅ Paystack available - Min deposit:', paystackMinDeposit);
            }
        }
    });
}

/**
 * Show Paystack deposit form
 */
function showPaystackDeposit() {
    // Hide all other deposit boxes
    $('.deposite-box').hide();
    // Show Paystack box
    $('#paystack').show();
    // Reset form
    resetPaystackForm();
}

/**
 * Reset Paystack form
 */
function resetPaystackForm() {
    $('#paystack_amount').val('');
    $('#paystack_email').val('');
    $('#paystack_status').hide();
    $('#paystack_loading').hide();
    $('#paystack_error').hide();
    $('#paystack_submit_btn').prop('disabled', false);
}

/**
 * Initiate Paystack payment
 */
function initiatePaystackDeposit() {
    var amount = $('#paystack_amount').val();
    var email = $('#paystack_email').val();
    var csrfToken = $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content');
    
    // Validation
    if (!email) {
        toastr.error('Please enter your email address');
        return;
    }
    
    if (!amount || parseFloat(amount) < paystackMinDeposit) {
        toastr.error('Minimum deposit amount is ' + paystackCurrencySymbol + paystackMinDeposit);
        return;
    }
    
    // Validate email format
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        toastr.error('Please enter a valid email address');
        return;
    }
    
    // Check if Paystack is configured
    if (!paystackPublicKey) {
        toastr.error('Paystack is not configured. Please contact support.');
        return;
    }
    
    // Disable button and show loading
    $('#paystack_submit_btn').prop('disabled', true);
    $('#paystack_status').show();
    $('#paystack_loading').show();
    $('#paystack_error').hide();
    
    // Initialize payment
    $.ajax({
        url: '/paystack/initialize',
        method: 'POST',
        data: {
            _token: csrfToken,
            amount: amount,
            email: email
        },
        success: function(response) {
            if (response.isSuccess) {
                toastr.success('Redirecting to payment page...');
                
                // Redirect to Paystack payment page
                setTimeout(function() {
                    window.location.href = response.authorization_url;
                }, 1000);
            } else {
                showPaystackError(response.message || 'Failed to initialize payment');
            }
        },
        error: function(xhr) {
            var errorMsg = 'Failed to initialize payment';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showPaystackError(errorMsg);
        }
    });
}

/**
 * Show error message
 */
function showPaystackError(message) {
    $('#paystack_loading').hide();
    $('#paystack_error').show();
    $('#paystack_error_msg').text(message);
    $('#paystack_submit_btn').prop('disabled', false);
    toastr.error(message);
}

/**
 * Pay with Paystack Popup (Alternative method using Paystack Inline)
 */
function payWithPaystackPopup() {
    var amount = $('#paystack_amount').val();
    var email = $('#paystack_email').val();
    
    // Validation
    if (!email || !amount || parseFloat(amount) < paystackMinDeposit) {
        toastr.error('Please fill in all required fields');
        return;
    }
    
    if (!paystackPublicKey) {
        toastr.error('Paystack is not configured');
        return;
    }
    
    // Convert amount to kobo (smallest currency unit)
    var amountInKobo = parseFloat(amount) * 100;
    
    var handler = PaystackPop.setup({
        key: paystackPublicKey,
        email: email,
        amount: amountInKobo,
        currency: paystackCurrency,
        ref: 'DEP_' + Date.now() + '_' + Math.random().toString(36).substring(7),
        metadata: {
            custom_fields: [
                {
                    display_name: "User Email",
                    variable_name: "user_email",
                    value: email
                }
            ]
        },
        callback: function(response) {
            // Payment successful
            toastr.success('Payment successful! Verifying...');
            
            // Verify the transaction on your server
            verifyPaystackPayment(response.reference);
        },
        onClose: function() {
            toastr.info('Payment cancelled');
            resetPaystackForm();
        }
    });
    
    handler.openIframe();
}

/**
 * Verify payment on server
 */
function verifyPaystackPayment(reference) {
    window.location.href = '/paystack/callback?reference=' + reference;
}

/**
 * Quick amount selection
 */
function setPaystackAmount(amount) {
    $('#paystack_amount').val(amount);
}
