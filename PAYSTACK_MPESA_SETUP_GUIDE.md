# Paystack M-Pesa Integration Guide

## Overview
Paystack provides a simplified M-Pesa integration that handles STK Push for deposits. For withdrawals, Paystack supports bank transfers to M-Pesa-linked accounts.

## What Paystack M-Pesa Offers

### ✅ Deposits (M-Pesa STK Push):
- User enters M-Pesa number
- Paystack sends STK Push prompt
- User enters PIN on phone
- Payment processed instantly
- Wallet credited automatically

### ✅ Withdrawals:
- Paystack Transfer Recipients API
- Send money to M-Pesa mobile money accounts
- Instant disbursements

## Step 1: Get Paystack API Keys

### Create/Login to Paystack Account
1. Go to https://dashboard.paystack.com
2. Sign up or login
3. Complete your profile

### Get API Keys
1. Navigate to **Settings** → **API Keys & Webhooks**
2. Copy your keys:
   - **Test Public Key**: `pk_test_xxxxxxxxxxxxxxxxxxxxxxxx`
   - **Test Secret Key**: `sk_test_xxxxxxxxxxxxxxxxxxxxxxxx`
   - **Live Public Key**: `pk_live_xxxxxxxxxxxxxxxxxxxxxxxx` (after KYC)
   - **Live Secret Key**: `sk_live_xxxxxxxxxxxxxxxxxxxxxxxx` (after KYC)

## Step 2: Configure Environment Variables

### Add to your `.env` file:

```env
# ============================================
# PAYSTACK M-PESA CONFIGURATION
# ============================================

# Paystack API Keys
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxxxxxxxxx

# Paystack URLs
PAYSTACK_CALLBACK_URL=https://yourdomain.com/paystack/callback
PAYSTACK_WEBHOOK_URL=https://yourdomain.com/paystack/webhook

# Merchant Settings
PAYSTACK_MERCHANT_EMAIL=support@yourdomain.com

# Currency (Kenya uses KES)
PAYSTACK_CURRENCY=KES
PAYSTACK_CURRENCY_SYMBOL=KSh

# Minimum Amounts
PAYSTACK_MIN_DEPOSIT=50
PAYSTACK_MIN_WITHDRAWAL=100

# Mobile Money Settings
PAYSTACK_MOBILE_MONEY_PROVIDER=mpesa
```

### For Local Testing (with Ngrok):
```env
PAYSTACK_CALLBACK_URL=https://xxxx-xx-xx.ngrok.io/paystack/callback
PAYSTACK_WEBHOOK_URL=https://xxxx-xx-xx.ngrok.io/paystack/webhook
```

## Step 3: Update Paystack Service

The `PaystackService.php` we created earlier already supports M-Pesa! But let's add M-Pesa specific methods:

### Add to `laravel/app/Services/PaystackService.php`:

```php
/**
 * Initialize Mobile Money (M-Pesa) payment
 * 
 * @param float $amount Amount in KES
 * @param string $phone M-Pesa phone number (254XXXXXXXXX)
 * @param string $email Customer email
 * @param string $reference Unique reference
 * @return array
 */
public function initializeMobileMoney($amount, $phone, $email, $reference = null)
{
    if (!$this->isConfigured()) {
        return [
            'success' => false,
            'message' => 'Paystack is not configured'
        ];
    }
    
    if (!$reference) {
        $reference = 'MPESA_' . time() . '_' . uniqid();
    }
    
    // Convert amount to kobo (cents)
    $amountInKobo = $amount * 100;
    
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transaction/initialize', [
            'email' => $email,
            'amount' => $amountInKobo,
            'reference' => $reference,
            'currency' => 'KES',
            'channels' => ['mobile_money'], // Only allow mobile money
            'metadata' => [
                'phone_number' => $phone,
                'payment_method' => 'mpesa',
                'custom_fields' => [
                    [
                        'display_name' => 'Phone Number',
                        'variable_name' => 'phone_number',
                        'value' => $phone
                    ]
                ]
            ],
            'callback_url' => $this->callbackUrl
        ]);
        
        $data = $response->json();
        
        if ($response->successful() && $data['status'] === true) {
            Log::info('Paystack M-Pesa initialized', [
                'reference' => $reference,
                'amount' => $amount,
                'phone' => $phone
            ]);
            
            return [
                'success' => true,
                'authorization_url' => $data['data']['authorization_url'],
                'access_code' => $data['data']['access_code'],
                'reference' => $data['data']['reference']
            ];
        }
        
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Failed to initialize payment'
        ];
        
    } catch (\Exception $e) {
        Log::error('Paystack M-Pesa initialization error', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'message' => 'Connection error: ' . $e->getMessage()
        ];
    }
}

/**
 * Create M-Pesa transfer recipient
 * 
 * @param string $phone M-Pesa phone number (254XXXXXXXXX)
 * @param string $name Account holder name
 * @return array
 */
public function createMpesaRecipient($phone, $name)
{
    if (!$this->isConfigured()) {
        return [
            'success' => false,
            'message' => 'Paystack is not configured'
        ];
    }
    
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/transferrecipient', [
            'type' => 'mobile_money',
            'name' => $name,
            'phone' => $phone,
            'currency' => 'KES',
            'metadata' => [
                'mobile_money_provider' => 'mpesa'
            ]
        ]);
        
        $data = $response->json();
        
        if ($response->successful() && $data['status'] === true) {
            return [
                'success' => true,
                'recipient_code' => $data['data']['recipient_code'],
                'details' => $data['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Failed to create recipient'
        ];
        
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}
```

## Step 4: Update Deposit JavaScript

Update `laravel/public/user/mpesa-deposit.js` to use Paystack:

```javascript
/**
 * Paystack M-Pesa Deposit Integration
 */

var paystackPublicKey = null;
var paystackMinDeposit = 50;

// Load Paystack config on page load
$(document).ready(function() {
    loadPaystackConfig();
});

function loadPaystackConfig() {
    $.ajax({
        url: '/paystack/config',
        method: 'GET',
        success: function(response) {
            paystackPublicKey = response.public_key;
            console.log('✅ Paystack configured for M-Pesa');
        },
        error: function() {
            console.error('❌ Failed to load Paystack config');
        }
    });
}

function showMpesaDeposit() {
    // Already visible
    $('#mpesa').show();
}

function initiateMpesaDeposit() {
    var phone = $('#mpesa_phone').val().trim();
    var amount = $('#mpesa_amount').val();
    var email = $('#mpesa_email').val() || 'user@example.com'; // Add email field or use default
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    // Validation
    if (!phone) {
        toastr.error('Please enter your M-Pesa phone number');
        return;
    }
    
    // Validate phone format (254XXXXXXXXX)
    if (!phone.match(/^254[0-9]{9}$/)) {
        toastr.error('Phone number must be in format: 254XXXXXXXXX');
        return;
    }
    
    if (!amount || parseFloat(amount) < paystackMinDeposit) {
        toastr.error('Minimum deposit is KSh ' + paystackMinDeposit);
        return;
    }
    
    // Show loading
    $('#mpesa_submit_btn').prop('disabled', true);
    $('#mpesa_status_box').show();
    $('#mpesa_loading').show();
    $('#mpesa_error').hide();
    $('#mpesa_success').hide();
    
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
                // Redirect to Paystack payment page
                toastr.success('Redirecting to M-Pesa payment...');
                window.location.href = response.authorization_url;
            } else {
                showMpesaError(response.message);
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

function showMpesaError(message) {
    $('#mpesa_loading').hide();
    $('#mpesa_error').show();
    $('#mpesa_error_msg').text(message);
    $('#mpesa_submit_btn').prop('disabled', false);
    toastr.error(message);
}
```

## Step 5: Create Paystack M-Pesa Controller Methods

Add to `laravel/app/Http/Controllers/PaystackController.php`:

```php
/**
 * Initialize M-Pesa deposit via Paystack
 */
public function initializeMpesaDeposit(Request $request)
{
    $request->validate([
        'phone' => 'required|string|regex:/^254[0-9]{9}$/',
        'amount' => 'required|numeric|min:' . config('paystack.min_deposit'),
        'email' => 'required|email'
    ]);
    
    if (!$this->paystack->isConfigured()) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Paystack is not configured'
        ], 503);
    }
    
    $userId = user('id');
    $phone = $request->phone;
    $amount = floatval($request->amount);
    $email = $request->email;
    
    // Generate reference
    $reference = 'MPESA_' . $userId . '_' . time();
    
    // Create transaction record
    try {
        $transaction = new Transaction();
        $transaction->userid = $userId;
        $transaction->amount = $amount;
        $transaction->category = 'deposit';
        $transaction->platform = 'Paystack M-Pesa';
        $transaction->transactionno = $reference;
        $transaction->status = 'pending';
        $transaction->remark = 'M-Pesa deposit via Paystack - ' . $phone;
        $transaction->save();
        
        // Initialize Paystack mobile money payment
        $result = $this->paystack->initializeMobileMoney(
            $amount,
            $phone,
            $email,
            $reference
        );
        
        if ($result['success']) {
            Log::info('Paystack M-Pesa deposit initialized', [
                'user_id' => $userId,
                'phone' => $phone,
                'amount' => $amount,
                'reference' => $reference
            ]);
            
            return response()->json([
                'isSuccess' => true,
                'message' => 'Redirecting to M-Pesa payment...',
                'authorization_url' => $result['authorization_url'],
                'reference' => $result['reference']
            ]);
        }
        
        // Failed
        $transaction->status = 'failed';
        $transaction->remark = 'Initialization failed: ' . $result['message'];
        $transaction->save();
        
        return response()->json([
            'isSuccess' => false,
            'message' => $result['message']
        ], 400);
        
    } catch (\Exception $e) {
        Log::error('Paystack M-Pesa initialization error', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to initialize payment'
        ], 500);
    }
}

/**
 * Initialize M-Pesa withdrawal via Paystack
 */
public function initializeMpesaWithdrawal(Request $request)
{
    $request->validate([
        'phone' => 'required|string|regex:/^254[0-9]{9}$/',
        'amount' => 'required|numeric|min:' . config('paystack.min_withdrawal')
    ]);
    
    $userId = user('id');
    $phone = $request->phone;
    $amount = floatval($request->amount);
    $userName = user('name');
    
    // Check wallet balance
    $wallet = Wallet::where('userid', $userId)->first();
    if (!$wallet || $wallet->balance < $amount) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Insufficient balance'
        ], 400);
    }
    
    DB::beginTransaction();
    
    try {
        // Deduct from wallet
        $wallet->balance -= $amount;
        $wallet->save();
        
        // Create transaction
        $reference = 'WITHDRAW_' . $userId . '_' . time();
        $transaction = new Transaction();
        $transaction->userid = $userId;
        $transaction->amount = $amount;
        $transaction->category = 'withdrawal';
        $transaction->platform = 'Paystack M-Pesa';
        $transaction->transactionno = $reference;
        $transaction->status = 'pending';
        $transaction->remark = 'M-Pesa withdrawal via Paystack - ' . $phone;
        $transaction->save();
        
        // Create M-Pesa recipient
        $recipientResult = $this->paystack->createMpesaRecipient($phone, $userName);
        
        if (!$recipientResult['success']) {
            throw new \Exception($recipientResult['message']);
        }
        
        $recipientCode = $recipientResult['recipient_code'];
        
        // Initiate transfer
        $transferResult = $this->paystack->initiateTransfer(
            $recipientCode,
            $amount,
            'Withdrawal to M-Pesa',
            $reference
        );
        
        if ($transferResult['success']) {
            $transaction->status = 'success';
            $transaction->remark = 'M-Pesa withdrawal successful - ' . $phone;
            $transaction->save();
            
            DB::commit();
            
            Log::info('Paystack M-Pesa withdrawal successful', [
                'user_id' => $userId,
                'phone' => $phone,
                'amount' => $amount
            ]);
            
            return response()->json([
                'isSuccess' => true,
                'message' => 'Withdrawal successful! Funds sent to your M-Pesa.'
            ]);
        }
        
        throw new \Exception($transferResult['message'] ?? 'Transfer failed');
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        // Refund wallet
        $wallet->balance += $amount;
        $wallet->save();
        
        if (isset($transaction)) {
            $transaction->status = 'failed';
            $transaction->remark = 'Failed: ' . $e->getMessage();
            $transaction->save();
        }
        
        Log::error('Paystack M-Pesa withdrawal error', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'isSuccess' => false,
            'message' => 'Withdrawal failed: ' . $e->getMessage()
        ], 500);
    }
}
```

## Step 6: Update Routes

Add to `laravel/routes/web.php`:

```php
// Paystack M-Pesa Routes
Route::prefix('paystack')->group(function () {
    Route::post('/mpesa/initialize', [PaystackController::class, 'initializeMpesaDeposit']);
    Route::post('/mpesa/withdraw', [PaystackController::class, 'initializeMpesaWithdrawal']);
    Route::get('/callback', [PaystackController::class, 'handleCallback']);
    Route::post('/webhook', [PaystackController::class, 'handleWebhook']);
    Route::get('/config', [PaystackController::class, 'getPublicKey']);
    Route::get('/availability', [PaystackController::class, 'checkAvailability']);
});
```

## Step 7: Update Deposit Page

Add email field to `deposite.blade.php`:

```html
<div class="col-12">
    <div class="login-controls mt-3 rounded-pill h42">
        <label for="mpesa_email" class="rounded-pill">
            <input type="email" class="form-control text-i10" 
                id="mpesa_email"
                placeholder="your@email.com"
                value="{{ user('email') ?? '' }}">
            <i class="Input_currency">
                📧 Email
            </i>
        </label>
    </div>
</div>
```

## Step 8: Configure Webhooks in Paystack

1. Login to https://dashboard.paystack.com
2. Go to **Settings** → **API Keys & Webhooks**
3. Click **Webhooks** tab
4. Add webhook URL: `https://yourdomain.com/paystack/webhook`
5. Select events:
   - ✅ charge.success
   - ✅ transfer.success
   - ✅ transfer.failed
6. Save

## Step 9: Local Testing with Ngrok

```bash
# Start Laravel
php artisan serve

# Start Ngrok (in new terminal)
ngrok http 8000

# Update .env with ngrok URL
PAYSTACK_CALLBACK_URL=https://xxxx.ngrok.io/paystack/callback
PAYSTACK_WEBHOOK_URL=https://xxxx.ngrok.io/paystack/webhook

# Clear cache
php artisan config:clear
```

## Step 10: Test M-Pesa Deposits

### Test Phone Numbers (Paystack Sandbox):
- `254708374149` - Success
- `254712345678` - Any valid format works in test mode

### Testing:
1. Navigate to `/deposit`
2. Enter phone: `254708374149`
3. Enter email: `test@example.com`
4. Enter amount: `100`
5. Click "PAY NOW"
6. You'll be redirected to Paystack payment page
7. Select "Mobile Money" → "M-Pesa"
8. In test mode, it auto-completes
9. Redirected back to your site
10. Wallet credited automatically

## Paystack M-Pesa vs Direct Safaricom Integration

| Feature | Paystack M-Pesa | Direct Safaricom |
|---------|----------------|------------------|
| Setup | Easy | Complex |
| KYC Required | Yes (for live) | Yes (for live) |
| Test Mode | Full test mode | Sandbox only |
| Fees | 3.9% + KSh 10 | 1% (direct) |
| Support | Paystack support | Self-managed |
| Updates | Auto-handled | Manual updates |
| Withdrawals | Via Transfers API | B2C API |
| PCI Compliance | Handled by Paystack | Self-managed |

## Benefits of Paystack M-Pesa

✅ **Easier Setup**: No Daraja API complexity
✅ **Better Testing**: Full test mode with real flow
✅ **Unified Dashboard**: All payments in one place
✅ **Auto Reconciliation**: Paystack handles settlement
✅ **Multiple Channels**: Can add cards, bank transfers later
✅ **Better Support**: Paystack handles M-Pesa issues
✅ **PCI Compliant**: Security handled by Paystack

## Going Live

1. **Complete Paystack KYC**
   - Submit business documents
   - Wait for approval

2. **Get Live Keys**
   - Switch to live keys in `.env`

3. **Update Webhook URL**
   - Use production domain (HTTPS)

4. **Test Small Transaction**
   - Make KSh 50 test deposit
   - Verify end-to-end

---

**Ready to Accept M-Pesa via Paystack!** 🚀
