# M-Pesa Local Testing Guide

## Overview
This guide will help you test M-Pesa deposits and withdrawals on your local development environment.

## Prerequisites

1. **M-Pesa Daraja API Account**
   - Go to: https://developer.safaricom.co.ke/
   - Create an account (it's free)
   - Create a test app

2. **Local Server Requirements**
   - PHP 7.4+ or 8.x
   - MySQL/MariaDB
   - Composer
   - Laravel installed

## Step 1: Get M-Pesa Test Credentials

### Login to Daraja Portal
1. Go to https://developer.safaricom.co.ke/login
2. Login with your credentials
3. Click "My Apps" → "Create New App"

### Create Test App
1. **App Name**: Your App Name (e.g., "Aviator Test")
2. **Select APIs**: 
   - ✅ Lipa Na M-Pesa Online (for deposits)
   - ✅ B2C (for withdrawals - optional for now)
3. Click "Create App"

### Get Your Credentials
After creating the app, you'll see:
- **Consumer Key**: `xxxxxxxxxxxxxxxxxxxxxxxxxx`
- **Consumer Secret**: `yyyyyyyyyyyyyyyyyyyyyyyy`

## Step 2: Configure Environment Variables

### Open your `.env` file and add:

```env
# ============================================
# M-PESA CONFIGURATION (SANDBOX/TEST)
# ============================================

# Consumer Keys from Daraja Portal
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here

# Environment (sandbox for testing, production for live)
MPESA_ENV=sandbox

# Lipa Na M-Pesa Online (STK Push) - For Deposits
MPESA_SHORTCODE=174379
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
MPESA_CALLBACK_URL=https://yourdomain.com/mpesa/callback

# B2C (Business to Customer) - For Withdrawals
MPESA_B2C_SHORTCODE=600000
MPESA_B2C_INITIATOR_NAME=testapi
MPESA_B2C_INITIATOR_PASSWORD=Safaricom999!*!
MPESA_B2C_QUEUE_TIMEOUT_URL=https://yourdomain.com/mpesa/b2c/timeout
MPESA_B2C_RESULT_URL=https://yourdomain.com/mpesa/b2c/result

# Test Phone Number Format
# Use 254708374149 for testing (Safaricom test number)
```

### Important Notes:
- The **Shortcode**, **Passkey**, and **Test Phone** are Safaricom's sandbox defaults
- The **Callback URL** needs to be publicly accessible (see Step 3)

## Step 3: Make Your Local Server Publicly Accessible

M-Pesa needs to send callbacks to your server. Since you're on localhost, you need a tunnel:

### Option 1: Using Ngrok (Recommended)

1. **Download Ngrok**
   ```bash
   # Visit: https://ngrok.com/download
   # Or use chocolatey on Windows:
   choco install ngrok
   ```

2. **Start Your Laravel Server**
   ```bash
   php artisan serve
   # Runs on http://127.0.0.1:8000
   ```

3. **Start Ngrok Tunnel**
   ```bash
   ngrok http 8000
   ```

4. **Copy the HTTPS URL**
   ```
   Ngrok will show:
   Forwarding: https://xxxx-xx-xx-xxx-xxx.ngrok.io -> http://localhost:8000
   ```

5. **Update .env with Ngrok URL**
   ```env
   MPESA_CALLBACK_URL=https://xxxx-xx-xx-xxx-xxx.ngrok.io/mpesa/callback
   MPESA_B2C_QUEUE_TIMEOUT_URL=https://xxxx-xx-xx-xxx-xxx.ngrok.io/mpesa/b2c/timeout
   MPESA_B2C_RESULT_URL=https://xxxx-xx-xx-xxx-xxx.ngrok.io/mpesa/b2c/result
   ```

### Option 2: Using Localtunnel

```bash
# Install
npm install -g localtunnel

# Start Laravel
php artisan serve

# Start tunnel
lt --port 8000

# Use the provided URL in .env
```

## Step 4: Clear Cache and Restart

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Restart your server
php artisan serve
```

## Step 5: Test Deposit (STK Push)

### Test Phone Numbers (Sandbox)
Safaricom provides these test numbers:
- **254708374149** - Successful transaction
- **254708374150** - Failed transaction (insufficient funds)
- **254708374151** - Cancelled by user

### Testing Steps:

1. **Navigate to Deposit Page**
   ```
   http://127.0.0.1:8000/deposit
   ```

2. **Enter Test Details**
   - Phone: `254708374149`
   - Amount: `100` (or any amount)

3. **Click "PAY NOW"**

4. **Check Browser Console**
   - Should show: "M-Pesa STK Push initiated"
   - Should see loading message

5. **What Happens in Sandbox**
   - In production: User receives STK Push prompt on phone
   - In sandbox: Auto-simulates payment after 30-60 seconds
   - You won't receive an actual phone prompt

6. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for:
   - "M-Pesa deposit initiated"
   - "M-Pesa callback received"
   - "Payment successful"

7. **Verify Wallet Credit**
   - Check your wallet balance
   - Should increase by the deposit amount
   - Check transactions table in database

## Step 6: Test Withdrawal (B2C)

### Testing Steps:

1. **Navigate to Withdraw Page**
   ```
   http://127.0.0.1:8000/withdraw
   ```

2. **Enter Test Details**
   - M-Pesa Number: `254708374149`
   - Amount: `500` (or any amount)

3. **Click "WITHDRAW"**

4. **What Happens in Sandbox**
   - Wallet is debited immediately
   - B2C request sent to M-Pesa
   - In sandbox: Auto-simulates success after delay
   - In production: Actual money sent to phone

5. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for:
   - "M-Pesa withdrawal initiated"
   - "B2C result received"
   - "Withdrawal successful"

## Step 7: Check Database

```sql
-- Check transactions
SELECT * FROM transactions 
WHERE userid = YOUR_USER_ID 
ORDER BY created_at DESC 
LIMIT 10;

-- Check wallet balance
SELECT * FROM wallets 
WHERE userid = YOUR_USER_ID;
```

## Common Issues & Solutions

### Issue 1: "M-Pesa is not configured"
**Solution:**
```bash
# Check .env has all required variables
php artisan config:clear
php artisan cache:clear
```

### Issue 2: "Callback not received"
**Solution:**
- Check ngrok is running
- Update callback URL in .env
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify route exists: `php artisan route:list | grep mpesa`

### Issue 3: "Invalid Access Token"
**Solution:**
- Verify Consumer Key and Secret in .env
- Check they're from the correct app in Daraja portal
- Make sure no extra spaces in .env values

### Issue 4: "Transaction timeout"
**Solution:**
- Sandbox can be slow (wait 60-90 seconds)
- Check your internet connection
- Try again with different test phone number

### Issue 5: "Invalid phone number format"
**Solution:**
- Must start with 254
- Example: 254708374149 (12 digits total)
- No spaces, no dashes, no +

## Testing Checklist

### Deposit Testing:
- [ ] .env configured with M-Pesa credentials
- [ ] Ngrok tunnel running
- [ ] Laravel server running
- [ ] Callback URL updated in .env
- [ ] Config cache cleared
- [ ] Navigate to /deposit
- [ ] Enter test phone: 254708374149
- [ ] Enter amount: 100
- [ ] Click "PAY NOW"
- [ ] Wait for callback (30-60 seconds)
- [ ] Verify wallet credited
- [ ] Check transaction in database
- [ ] Check logs for success message

### Withdrawal Testing:
- [ ] Ensure wallet has balance
- [ ] Navigate to /withdraw
- [ ] Enter M-Pesa number: 254708374149
- [ ] Enter amount: 500
- [ ] Click "WITHDRAW"
- [ ] Verify wallet debited
- [ ] Check transaction in database
- [ ] Check logs for B2C result

## Monitoring & Debugging

### Real-Time Log Monitoring
```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log | grep -i mpesa

# Watch all logs
tail -f storage/logs/laravel.log
```

### Enable Debug Mode
In `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Test M-Pesa Connection
Create a test route to verify credentials:

```php
// routes/web.php
Route::get('/test-mpesa', function() {
    $service = app(App\Services\MpesaService::class);
    $token = $service->getAccessToken();
    
    if ($token) {
        return "✅ M-Pesa connection successful!";
    } else {
        return "❌ M-Pesa connection failed. Check credentials.";
    }
});
```

Visit: `http://127.0.0.1:8000/test-mpesa`

## Sandbox vs Production

### Sandbox (Testing):
- Uses test credentials
- Uses test phone numbers
- Auto-simulates transactions
- No real money involved
- Slower response times

### Production (Live):
- Uses live credentials (after KYC approval)
- Uses real phone numbers
- Real transactions
- Real money
- Faster response times
- Requires business verification

## Going Live

After successful testing:

1. **Complete KYC**
   - Submit business documents to Safaricom
   - Wait for approval (3-5 business days)

2. **Get Live Credentials**
   - Login to Daraja portal
   - Create production app
   - Get live Consumer Key/Secret

3. **Update .env**
   ```env
   MPESA_ENV=production
   MPESA_CONSUMER_KEY=live_key_here
   MPESA_CONSUMER_SECRET=live_secret_here
   MPESA_SHORTCODE=your_live_shortcode
   MPESA_PASSKEY=your_live_passkey
   ```

4. **Update Callback URLs**
   - Use your actual domain (not ngrok)
   - Must be HTTPS

5. **Test with Small Amount**
   - Make a real KSh 10 deposit
   - Verify it works end-to-end

## Support & Resources

- **Daraja API Docs**: https://developer.safaricom.co.ke/docs
- **Test Credentials**: https://developer.safaricom.co.ke/test_credentials
- **Support**: apisupport@safaricom.co.ke
- **Community**: Safaricom Developer Forums

## Quick Start Summary

```bash
# 1. Get credentials from https://developer.safaricom.co.ke/
# 2. Update .env with credentials
# 3. Start Laravel
php artisan serve

# 4. Start ngrok (in new terminal)
ngrok http 8000

# 5. Update callback URL in .env with ngrok URL
# 6. Clear cache
php artisan config:clear

# 7. Test deposit: http://127.0.0.1:8000/deposit
#    Phone: 254708374149
#    Amount: 100

# 8. Monitor logs
tail -f storage/logs/laravel.log
```

---

**Ready to Test!** 🚀

Follow the steps above and you'll be able to test M-Pesa payments locally. Remember, sandbox transactions take 30-60 seconds to complete.
