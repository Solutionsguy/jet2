# Paystack Quick Start Checklist

## ✅ Pre-Integration Checklist

### 1. Paystack Account Setup
- [ ] Create account at https://dashboard.paystack.com
- [ ] Verify email address
- [ ] Complete business profile
- [ ] Get TEST API keys from Settings → API Keys & Webhooks

### 2. File Verification
Ensure these files exist in your project:
- [ ] `laravel/app/Services/PaystackService.php`
- [ ] `laravel/app/Http/Controllers/PaystackController.php`
- [ ] `laravel/config/paystack.php`
- [ ] `laravel/public/user/paystack-deposit.js`
- [ ] Routes added to `laravel/routes/web.php`

## 🚀 Quick Setup (5 Minutes)

### Step 1: Add Environment Variables
```bash
# Copy to your .env file
PAYSTACK_PUBLIC_KEY=pk_test_your_key_here
PAYSTACK_SECRET_KEY=sk_test_your_key_here
PAYSTACK_CALLBACK_URL=https://yourdomain.com/paystack/callback
PAYSTACK_MERCHANT_EMAIL=support@yourdomain.com
PAYSTACK_MIN_DEPOSIT=100
PAYSTACK_CURRENCY=NGN
PAYSTACK_CURRENCY_SYMBOL=₦
```

### Step 2: Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
```

### Step 3: Add to Deposit Page

Add this to `laravel/resources/views/deposite.blade.php`:

**In the `<head>` section:**
```html
<script src="https://js.paystack.co/v1/inline.js"></script>
```

**In the body (where deposit options are):**
```html
<!-- Paystack Payment Option -->
<button class="btn btn-success mb-3" onclick="showPaystackDeposit()">
    <i class="mdi mdi-credit-card"></i> Pay with Paystack (Cards, Bank Transfer, USSD)
</button>

<!-- Paystack Deposit Form -->
<div class="deposite-box" id="paystack" style="display: none;">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5><i class="mdi mdi-credit-card"></i> Paystack Payment</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label>Email Address *</label>
                <input type="email" class="form-control" id="paystack_email" 
                       value="{{ user('email') ?? '' }}" required>
            </div>
            
            <div class="mb-3">
                <label>Amount (₦) *</label>
                <input type="number" class="form-control" id="paystack_amount" 
                       placeholder="Minimum: ₦100" min="100" required>
            </div>
            
            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPaystackAmount(500)">₦500</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPaystackAmount(1000)">₦1,000</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPaystackAmount(5000)">₦5,000</button>
            </div>
            
            <div id="paystack_status" style="display: none;">
                <div id="paystack_loading" class="alert alert-info">
                    <i class="mdi mdi-loading mdi-spin"></i> Processing...
                </div>
                <div id="paystack_error" class="alert alert-danger" style="display: none;">
                    <span id="paystack_error_msg"></span>
                </div>
            </div>
            
            <button class="btn btn-success btn-block" id="paystack_submit_btn" 
                    onclick="initiatePaystackDeposit()">
                <i class="mdi mdi-lock"></i> Pay with Paystack
            </button>
            
            <small class="text-muted mt-2 d-block text-center">
                <i class="mdi mdi-shield-check"></i> Secured by Paystack
            </small>
        </div>
    </div>
</div>
```

**Before closing `</body>` tag:**
```html
<script src="{{ asset('user/paystack-deposit.js') }}"></script>
```

### Step 4: Configure Webhook in Paystack Dashboard

1. Go to https://dashboard.paystack.com/settings/developer
2. Click **Webhooks** tab
3. Enter webhook URL: `https://yourdomain.com/paystack/webhook`
4. Select events:
   - ✅ charge.success
   - ✅ transfer.success
   - ✅ transfer.failed
5. Click **Save**

## 🧪 Testing (Test Mode)

### Test Cards
Use these test cards (from Paystack):

**Successful Payment:**
```
Card: 4084 0840 8408 4081
CVV: Any 3 digits
Expiry: Any future date
PIN: 0000
OTP: 123456
```

**Declined Payment:**
```
Card: 5078 5078 5078 5078 5100
```

**Insufficient Funds:**
```
Card: 5060 6666 6666 6666 6666
```

### Test Workflow
1. Navigate to `/deposit`
2. Click "Pay with Paystack"
3. Enter email and amount (e.g., ₦500)
4. Click "Pay with Paystack" button
5. Use test card above
6. Complete payment
7. Verify wallet is credited
8. Check transaction in admin panel

## 📊 Verify Installation

### Backend Check
```bash
# Test if Paystack config is loaded
php artisan tinker
>>> config('paystack.public_key')
# Should show your public key

>>> app(App\Services\PaystackService::class)->isConfigured()
# Should return true
```

### Frontend Check
Open browser console on deposit page:
```javascript
// Should show Paystack config
console.log('Paystack configured');
```

### API Endpoint Check
Test endpoints:
```bash
# Check availability
curl https://yourdomain.com/paystack/availability

# Should return: {"available":true,"min_deposit":100,"currency":"NGN"}
```

## ✅ Go-Live Checklist

Before switching to production:

### 1. KYC Completion
- [ ] Submit business documents to Paystack
- [ ] Wait for approval (1-3 business days)
- [ ] Receive live API keys

### 2. Environment Setup
- [ ] Switch to LIVE keys in `.env`
- [ ] Update webhook URL to production URL
- [ ] Ensure HTTPS is enabled
- [ ] Test webhook delivery

### 3. Final Testing
- [ ] Make small real transaction (₦100)
- [ ] Verify funds credited to wallet
- [ ] Check Paystack dashboard for transaction
- [ ] Verify webhook received
- [ ] Test on mobile device

### 4. Monitoring
- [ ] Set up error monitoring
- [ ] Enable email notifications
- [ ] Monitor logs for first week
- [ ] Check settlement reports

## 🔧 Common Issues & Fixes

### "Paystack is not configured"
**Fix**: Check `.env` has valid keys, run `php artisan config:clear`

### Payment success but wallet not credited
**Fix**: Check webhook is configured, check logs at `storage/logs/laravel.log`

### Test card not working
**Fix**: Ensure using TEST keys, use exact test card from above

### Callback URL not working
**Fix**: Ensure route is in `web.php` middleware group, check HTTPS

## 📞 Support

- **Paystack Docs**: https://paystack.com/docs
- **Support Email**: support@paystack.com
- **Status Page**: https://status.paystack.com
- **Test Cards**: https://paystack.com/docs/payments/test-payments

## 🎯 Next Steps After Setup

1. Test thoroughly with test cards
2. Add transaction email notifications
3. Implement withdrawal support (optional)
4. Add payment analytics
5. Set up automatic settlements

---

**Estimated Setup Time**: 15-30 minutes
**Difficulty**: Easy
**Status**: Ready to Implement
