# Paystack M-Pesa Quick Start Checklist

## ✅ What You Need to Do

### 1. Get Paystack API Keys (5 minutes)
```
1. Go to: https://dashboard.paystack.com
2. Sign up or login
3. Navigate to: Settings → API Keys & Webhooks
4. Copy your keys:
   - Test Public Key: pk_test_xxxx
   - Test Secret Key: sk_test_xxxx
```

### 2. Update .env File (2 minutes)
Add these to your `.env`:

```env
# Paystack M-Pesa Configuration
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxxxxxxxxx
PAYSTACK_CALLBACK_URL=https://yourdomain.com/paystack/callback
PAYSTACK_MERCHANT_EMAIL=support@yourdomain.com
PAYSTACK_CURRENCY=KES
PAYSTACK_CURRENCY_SYMBOL=KSh
PAYSTACK_MIN_DEPOSIT=50
PAYSTACK_MIN_WITHDRAWAL=100
```

### 3. Update PaystackService (Already Done!)
The `PaystackService.php` we created earlier already supports M-Pesa!

You just need to add these two methods to `laravel/app/Services/PaystackService.php`:

**Add after line 258 (after the `verifyAccountNumber` method):**

```php
/**
 * Initialize Mobile Money (M-Pesa) payment
 */
public function initializeMobileMoney($amount, $phone, $email, $reference = null)
{
    if (!$this->isConfigured()) {
        return ['success' => false, 'message' => 'Paystack is not configured'];
    }
    
    if (!$reference) {
        $reference = 'MPESA_' . time() . '_' . uniqid();
    }
    
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
            'channels' => ['mobile_money'],
            'metadata' => [
                'phone_number' => $phone,
                'payment_method' => 'mpesa',
            ],
            'callback_url' => $this->callbackUrl
        ]);
        
        $data = $response->json();
        
        if ($response->successful() && $data['status'] === true) {
            return [
                'success' => true,
                'authorization_url' => $data['data']['authorization_url'],
                'access_code' => $data['data']['access_code'],
                'reference' => $data['data']['reference']
            ];
        }
        
        return ['success' => false, 'message' => $data['message'] ?? 'Failed'];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Create M-Pesa transfer recipient
 */
public function createMpesaRecipient($phone, $name)
{
    if (!$this->isConfigured()) {
        return ['success' => false, 'message' => 'Paystack is not configured'];
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
            'metadata' => ['mobile_money_provider' => 'mpesa']
        ]);
        
        $data = $response->json();
        
        if ($response->successful() && $data['status'] === true) {
            return [
                'success' => true,
                'recipient_code' => $data['data']['recipient_code']
            ];
        }
        
        return ['success' => false, 'message' => $data['message'] ?? 'Failed'];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

### 4. Update PaystackController (Add M-Pesa Methods)

Add to `laravel/app/Http/Controllers/PaystackController.php`:

**Add after the `checkAvailability()` method:**

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
    
    $userId = user('id');
    $phone = $request->phone;
    $amount = floatval($request->amount);
    $email = $request->email;
    $reference = 'MPESA_' . $userId . '_' . time();
    
    try {
        $transaction = new Transaction();
        $transaction->userid = $userId;
        $transaction->amount = $amount;
        $transaction->category = 'deposit';
        $transaction->platform = 'Paystack M-Pesa';
        $transaction->transactionno = $reference;
        $transaction->status = 'pending';
        $transaction->remark = 'M-Pesa deposit - ' . $phone;
        $transaction->save();
        
        $result = $this->paystack->initializeMobileMoney($amount, $phone, $email, $reference);
        
        if ($result['success']) {
            return response()->json([
                'isSuccess' => true,
                'authorization_url' => $result['authorization_url'],
                'reference' => $result['reference']
            ]);
        }
        
        $transaction->status = 'failed';
        $transaction->save();
        
        return response()->json([
            'isSuccess' => false,
            'message' => $result['message']
        ], 400);
        
    } catch (\Exception $e) {
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
    
    $wallet = Wallet::where('userid', $userId)->first();
    if (!$wallet || $wallet->balance < $amount) {
        return response()->json(['isSuccess' => false, 'message' => 'Insufficient balance'], 400);
    }
    
    DB::beginTransaction();
    
    try {
        $wallet->balance -= $amount;
        $wallet->save();
        
        $reference = 'WITHDRAW_' . $userId . '_' . time();
        $transaction = new Transaction();
        $transaction->userid = $userId;
        $transaction->amount = $amount;
        $transaction->category = 'withdrawal';
        $transaction->platform = 'Paystack M-Pesa';
        $transaction->transactionno = $reference;
        $transaction->status = 'pending';
        $transaction->save();
        
        $recipientResult = $this->paystack->createMpesaRecipient($phone, $userName);
        if (!$recipientResult['success']) {
            throw new \Exception($recipientResult['message']);
        }
        
        $transferResult = $this->paystack->initiateTransfer(
            $recipientResult['recipient_code'],
            $amount,
            'Withdrawal',
            $reference
        );
        
        if ($transferResult['success']) {
            $transaction->status = 'success';
            $transaction->save();
            DB::commit();
            
            return response()->json([
                'isSuccess' => true,
                'message' => 'Withdrawal successful!'
            ]);
        }
        
        throw new \Exception($transferResult['message'] ?? 'Transfer failed');
        
    } catch (\Exception $e) {
        DB::rollBack();
        $wallet->balance += $amount;
        $wallet->save();
        
        return response()->json([
            'isSuccess' => false,
            'message' => 'Withdrawal failed'
        ], 500);
    }
}
```

### 5. Update Routes

Add to `laravel/routes/web.php` (inside the Paystack group):

```php
Route::prefix('paystack')->group(function () {
    Route::post('/mpesa/initialize', [\App\Http\Controllers\PaystackController::class, 'initializeMpesaDeposit']);
    Route::post('/mpesa/withdraw', [\App\Http\Controllers\PaystackController::class, 'initializeMpesaWithdrawal']);
    Route::post('/initialize', [\App\Http\Controllers\PaystackController::class, 'initializeDeposit']);
    Route::get('/callback', [\App\Http\Controllers\PaystackController::class, 'handleCallback']);
    Route::post('/webhook', [\App\Http\Controllers\PaystackController::class, 'handleWebhook']);
    Route::get('/config', [\App\Http\Controllers\PaystackController::class, 'getPublicKey']);
    Route::get('/availability', [\App\Http\Controllers\PaystackController::class, 'checkAvailability']);
});
```

### 6. Update Deposit Page - Add Email Field

In `laravel/resources/views/deposite.blade.php`, add email field after phone:

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

### 7. Update JavaScript Files

Replace `laravel/public/user/mpesa-deposit.js` with:

```javascript
function initiateMpesaDeposit() {
    var phone = $('#mpesa_phone').val().trim();
    var amount = $('#mpesa_amount').val();
    var email = $('#mpesa_email').val() || '{{ user("email") }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    if (!phone.match(/^254[0-9]{9}$/)) {
        toastr.error('Phone format: 254XXXXXXXXX');
        return;
    }
    
    if (!amount || parseFloat(amount) < 50) {
        toastr.error('Minimum deposit is KSh 50');
        return;
    }
    
    $('#mpesa_submit_btn').prop('disabled', true);
    $('#mpesa_status_box').show();
    $('#mpesa_loading').show();
    
    $.ajax({
        url: '/paystack/mpesa/initialize',
        method: 'POST',
        data: { _token: csrfToken, phone: phone, amount: amount, email: email },
        success: function(response) {
            if (response.isSuccess) {
                toastr.success('Redirecting to M-Pesa...');
                window.location.href = response.authorization_url;
            } else {
                toastr.error(response.message);
                $('#mpesa_submit_btn').prop('disabled', false);
                $('#mpesa_loading').hide();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Payment failed');
            $('#mpesa_submit_btn').prop('disabled', false);
            $('#mpesa_loading').hide();
        }
    });
}
```

Replace `laravel/public/user/mpesa-withdraw.js` with:

```javascript
function initiateMpesaWithdraw() {
    var phone = $('#mpesa_withdraw_phone').val().trim();
    var amount = $('#mpesa_withdraw_amount').val();
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    if (!phone.match(/^254[0-9]{9}$/)) {
        toastr.error('Phone format: 254XXXXXXXXX');
        return;
    }
    
    if (!amount || parseFloat(amount) < 100) {
        toastr.error('Minimum withdrawal is KSh 100');
        return;
    }
    
    $('#mpesa_withdraw_btn').prop('disabled', true);
    $('#mpesa_withdraw_status').show();
    $('#mpesa_withdraw_loading').show();
    
    $.ajax({
        url: '/paystack/mpesa/withdraw',
        method: 'POST',
        data: { _token: csrfToken, phone: phone, amount: amount },
        success: function(response) {
            if (response.isSuccess) {
                $('#mpesa_withdraw_loading').hide();
                $('#mpesa_withdraw_success').show();
                toastr.success(response.message);
                
                if (typeof updateWalletBalanceFromServer === 'function') {
                    updateWalletBalanceFromServer();
                }
                
                setTimeout(function() {
                    $('#mpesa_withdraw_status').hide();
                    $('#mpesa_withdraw_phone').val('');
                    $('#mpesa_withdraw_amount').val('');
                    $('#mpesa_withdraw_btn').prop('disabled', false);
                }, 2000);
            } else {
                toastr.error(response.message);
                $('#mpesa_withdraw_btn').prop('disabled', false);
                $('#mpesa_withdraw_loading').hide();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Withdrawal failed');
            $('#mpesa_withdraw_btn').prop('disabled', false);
            $('#mpesa_withdraw_loading').hide();
        }
    });
}
```

### 8. Configure Webhook (Important!)

1. Login to Paystack Dashboard
2. Go to Settings → API Keys & Webhooks → Webhooks
3. Add: `https://yourdomain.com/paystack/webhook`
4. Select: charge.success, transfer.success, transfer.failed

### 9. Local Testing Setup

```bash
# 1. Clear cache
php artisan config:clear
php artisan cache:clear

# 2. Start Laravel
php artisan serve

# 3. Start Ngrok (new terminal)
ngrok http 8000

# 4. Copy ngrok URL (e.g., https://xxxx.ngrok.io)
# 5. Update .env:
PAYSTACK_CALLBACK_URL=https://xxxx.ngrok.io/paystack/callback
PAYSTACK_WEBHOOK_URL=https://xxxx.ngrok.io/paystack/webhook

# 6. Clear cache again
php artisan config:clear
```

### 10. Test!

**Deposit Test:**
```
1. Go to: http://127.0.0.1:8000/deposit
2. Phone: 254708374149
3. Email: test@example.com
4. Amount: 100
5. Click "PAY NOW"
6. Redirected to Paystack → Select M-Pesa
7. In test mode: auto-completes
8. Redirected back → wallet credited!
```

**Withdrawal Test:**
```
1. Go to: http://127.0.0.1:8000/withdraw
2. Phone: 254708374149
3. Amount: 500
4. Click "WITHDRAW"
5. Success message → wallet debited!
```

## 🎯 Summary

**Paystack M-Pesa vs Direct Safaricom:**
- ✅ **Easier**: No complex Daraja API setup
- ✅ **Faster**: Ready in 30 minutes
- ✅ **Better Testing**: Full test mode
- ✅ **More Reliable**: Paystack handles M-Pesa issues
- ✅ **Unified**: All payments in one dashboard

**You Need:**
1. Paystack account ✅
2. API keys in .env ✅
3. Two new methods in PaystackService ✅
4. Two new methods in PaystackController ✅
5. Updated routes ✅
6. Email field in deposit form ✅
7. Updated JavaScript ✅

That's it! You're ready to accept M-Pesa via Paystack! 🚀

---

**Next Steps After Testing:**
1. Complete Paystack KYC for live mode
2. Switch to live API keys
3. Update webhook URL to production domain
4. Go live!
