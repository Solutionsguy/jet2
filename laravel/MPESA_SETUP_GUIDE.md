# M-Pesa Integration Guide (STK Push & B2C)

This guide explains how to configure and set up M-Pesa integration for your Aviator game:
- **STK Push (C2B)**: For deposits - customers pay to your business
- **B2C**: For withdrawals - send money from your business to customers

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Safaricom Developer Portal Setup](#safaricom-developer-portal-setup)
4. [Environment Configuration](#environment-configuration)
5. [B2C Withdrawal Setup](#b2c-withdrawal-setup)
6. [Going Live (Production)](#going-live-production)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The M-Pesa integration allows users to deposit funds using the STK Push method, where:
1. User enters their phone number and amount
2. An STK Push prompt is sent to their phone
3. User enters their M-Pesa PIN to confirm
4. Payment is automatically credited to their account

---

## Prerequisites

Before setting up M-Pesa, ensure you have:
- [ ] A Safaricom M-Pesa Paybill or Till Number (for production)
- [ ] Access to [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
- [ ] A publicly accessible HTTPS URL for callbacks (required for production)
- [ ] PHP 7.4+ with cURL extension enabled

---

## Safaricom Developer Portal Setup

### Step 1: Create Developer Account

1. Go to [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Click **Sign Up** and create an account
3. Verify your email address

### Step 2: Create an App

1. Log in to the Developer Portal
2. Go to **My Apps** → **Add a New App**
3. Fill in the app details:
   - **App Name**: Aviator (or your preferred name)
   - **App Description**: Payment integration for Aviator game
4. Select the following APIs:
   - ✅ **Lipa Na M-Pesa Online** (STK Push)
   - ✅ **M-Pesa Express Query** (for checking transaction status)
5. Click **Create App**

### Step 3: Get API Credentials

After creating the app, you'll see:

| Credential | Description | Where to Add |
|------------|-------------|--------------|
| **Consumer Key** | Your app's unique identifier | `MPESA_CONSUMER_KEY` in `.env` |
| **Consumer Secret** | Your app's secret key | `MPESA_CONSUMER_SECRET` in `.env` |

⚠️ **Keep these credentials secure! Never commit them to version control.**

### Step 4: Get Sandbox Test Credentials

For testing, Safaricom provides sandbox credentials:

1. Go to **APIs** → **Lipa Na M-Pesa Online** → **Simulate**
2. Note the sandbox test credentials:

| Sandbox Credential | Value | Where to Add |
|-------------------|-------|--------------|
| **Business Short Code** | `174379` | `MPESA_SHORTCODE` in `.env` |
| **Passkey** | (Provided on the page) | `MPESA_PASSKEY` in `.env` |
| **Test Phone Numbers** | `254708374149` | Use for testing |

---

## Environment Configuration

### Step 1: Copy Environment Variables

Add the following to your `.env` file (copy from `.env.example`):

```env
# ===========================================
# M-PESA CONFIGURATION (Lipa Na M-Pesa STK Push)
# ===========================================

# Environment: 'sandbox' for testing, 'production' for live
MPESA_ENVIRONMENT=sandbox

# Get these from Safaricom Developer Portal
# Developer Portal: https://developer.safaricom.co.ke/
MPESA_CONSUMER_KEY=YOUR_CONSUMER_KEY_HERE
MPESA_CONSUMER_SECRET=YOUR_CONSUMER_SECRET_HERE

# Your M-Pesa Business Shortcode (Paybill or Till Number)
# Sandbox: 174379
# Production: Your actual Paybill/Till number
MPESA_SHORTCODE=174379

# Lipa Na M-Pesa Online Passkey
# Sandbox: Get from Developer Portal (APIs → Lipa Na M-Pesa → Simulate)
# Production: Request from Safaricom after Go-Live
MPESA_PASSKEY=YOUR_PASSKEY_HERE

# Callback URL - MUST be publicly accessible HTTPS URL
# This is where Safaricom sends payment confirmations
# Example: https://yourdomain.com/mpesa/callback
MPESA_CALLBACK_URL=https://yourdomain.com/mpesa/callback

# Transaction Type
# 'CustomerPayBillOnline' for Paybill numbers
# 'CustomerBuyGoodsOnline' for Till numbers
MPESA_TRANSACTION_TYPE=CustomerPayBillOnline

# Account reference shown to customer (max 12 characters)
MPESA_ACCOUNT_REFERENCE=Aviator

# Transaction description (max 13 characters)
MPESA_TRANSACTION_DESC=Deposit
```

### Step 2: Configure Each Variable

#### `MPESA_ENVIRONMENT`
```env
# For testing:
MPESA_ENVIRONMENT=sandbox

# For production:
MPESA_ENVIRONMENT=production
```

#### `MPESA_CONSUMER_KEY` & `MPESA_CONSUMER_SECRET`
```env
# Get from Developer Portal → My Apps → Your App
MPESA_CONSUMER_KEY=abc123xyz...
MPESA_CONSUMER_SECRET=def456uvw...
```

#### `MPESA_SHORTCODE`
```env
# Sandbox (for testing):
MPESA_SHORTCODE=174379

# Production (your actual business number):
MPESA_SHORTCODE=123456
```

#### `MPESA_PASSKEY`
```env
# Sandbox: Get from Developer Portal → APIs → Lipa Na M-Pesa → Simulate
# Look for "Lipa Na Mpesa Online Passkey"
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919

# Production: Request from Safaricom (see Going Live section)
```

#### `MPESA_CALLBACK_URL`
```env
# MUST be:
# 1. Publicly accessible (not localhost)
# 2. HTTPS (SSL certificate required)
# 3. The exact URL including /mpesa/callback

# Example:
MPESA_CALLBACK_URL=https://yourdomain.com/mpesa/callback

# For local testing, use ngrok:
# 1. Run: ngrok http 8000
# 2. Use the https URL: https://abc123.ngrok.io/mpesa/callback
```

---

## B2C Withdrawal Setup

B2C (Business to Customer) allows you to send money directly to your users' M-Pesa accounts for instant withdrawals.

### Step 1: Enable B2C on Developer Portal

1. Log in to [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Go to **My Apps** → Select your app
3. Add the **B2C API** to your app:
   - ✅ **Business To Customer (B2C)**

### Step 2: Get B2C Credentials

For B2C, you need additional credentials:

| Credential | Description | Where to Get |
|------------|-------------|--------------|
| **Initiator Name** | API operator username | Safaricom provides after B2C approval |
| **Security Credential** | Encrypted initiator password | Generate using Safaricom's certificate |

### Step 3: Generate Security Credential

The Security Credential is your initiator password encrypted with Safaricom's certificate.

**For Sandbox:**
1. Download sandbox certificate from Developer Portal
2. Use this online tool or OpenSSL to encrypt your password

**Using OpenSSL:**
```bash
# Download the certificate first, then:
echo -n "YourInitiatorPassword" | openssl rsautl -encrypt -pubin -inkey SandboxCertificate.cer -out encrypted.txt
base64 encrypted.txt
```

**For Production:**
- Safaricom will provide the production certificate
- Use the same process with the production certificate

### Step 4: Configure B2C Environment Variables

Add these to your `.env` file:

```env
# ===========================================
# M-PESA B2C CONFIGURATION (For Withdrawals)
# ===========================================

# Initiator Name - API operator username (from Safaricom)
MPESA_B2C_INITIATOR_NAME=testapi

# Security Credential - Encrypted password
# Sandbox: Use sandbox certificate to encrypt
# Production: Use production certificate to encrypt
MPESA_B2C_SECURITY_CREDENTIAL=Safaricom123!encrypted...

# Command ID options:
# - BusinessPayment: General B2C payment
# - SalaryPayment: Salary disbursement
# - PromotionPayment: Promotional payment
MPESA_B2C_COMMAND_ID=BusinessPayment

# B2C Result URL - Safaricom sends withdrawal results here
MPESA_B2C_RESULT_URL=https://yourdomain.com/mpesa/b2c/result

# B2C Timeout URL - Receives timeout notifications
MPESA_B2C_TIMEOUT_URL=https://yourdomain.com/mpesa/b2c/timeout

# Remarks shown in recipient's M-Pesa statement
MPESA_B2C_REMARKS=Aviator Withdrawal

# Occasion/reference
MPESA_B2C_OCCASION=Withdrawal
```

### Step 5: B2C Sandbox Testing

For sandbox testing, use these test credentials:

| Sandbox Credential | Value |
|-------------------|-------|
| **Initiator Name** | `testapi` |
| **Test Shortcode** | `600000` (B2C shortcode) |
| **Test Password** | (Get from Developer Portal) |

**Note:** The sandbox B2C shortcode (`600000`) is different from the STK Push shortcode (`174379`).

### B2C Security Considerations

⚠️ **Important B2C Security Notes:**

1. **Balance Management**: Ensure your B2C account has sufficient balance
2. **Transaction Limits**: Set daily/transaction limits in Safaricom portal
3. **IP Whitelisting**: Whitelist your server IP for production
4. **Monitor Balance**: Implement alerts for low B2C balance
5. **Refund Logic**: The system auto-refunds failed withdrawals

---

## Going Live (Production)

### Step 1: Apply for Production Credentials

1. Log in to [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Go to **Go Live** section
3. Fill the application form with:
   - Business details
   - M-Pesa Paybill/Till number
   - Technical contact information
   - Callback URLs

### Step 2: Complete KYC Requirements

Safaricom will require:
- Business registration documents
- M-Pesa Merchant Agreement
- Technical integration test results

### Step 3: Get Production Credentials

After approval, you'll receive:
- Production Consumer Key
- Production Consumer Secret
- Production Passkey
- Production Shortcode confirmation

### Step 4: Update Environment

```env
MPESA_ENVIRONMENT=production
MPESA_CONSUMER_KEY=your_production_consumer_key
MPESA_CONSUMER_SECRET=your_production_consumer_secret
MPESA_SHORTCODE=your_paybill_or_till_number
MPESA_PASSKEY=your_production_passkey
MPESA_CALLBACK_URL=https://yourdomain.com/mpesa/callback
```

---

## Testing

### Sandbox Testing

1. Ensure environment is set to sandbox:
   ```env
   MPESA_ENVIRONMENT=sandbox
   ```

2. Use Safaricom test phone numbers:
   - `254708374149`
   - `254708374150`
   - Any Kenyan format number works in sandbox

3. Test amounts: Use any amount (minimum based on your `min_recharge` setting)

### Testing Workflow

1. Go to Deposit page
2. Click M-Pesa payment option
3. Enter test phone number: `0708374149`
4. Enter amount: `100`
5. Click "Pay with M-Pesa"
6. In sandbox, payment is auto-approved after a few seconds

### Checking Logs

Monitor M-Pesa logs:
```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep -i mpesa
```

---

## Troubleshooting

### Common Issues

#### 1. "Failed to authenticate with M-Pesa"
**Cause**: Invalid Consumer Key or Consumer Secret
**Solution**: 
- Verify credentials in Developer Portal
- Ensure no extra spaces in `.env` values
- Clear config cache: `php artisan config:clear`

#### 2. "Invalid Access Token"
**Cause**: Expired or invalid token
**Solution**:
- Clear cache: `php artisan cache:clear`
- Check Consumer Key/Secret
- Ensure `MPESA_ENVIRONMENT` matches your credentials

#### 3. "Bad Request - Invalid BusinessShortCode"
**Cause**: Wrong shortcode for environment
**Solution**:
- Sandbox: Use `174379`
- Production: Use your actual Paybill/Till number

#### 4. "Callback URL not reachable"
**Cause**: Callback URL is not publicly accessible
**Solution**:
- Ensure HTTPS URL
- For local testing, use ngrok
- Check firewall settings

#### 5. "The initiator information is invalid"
**Cause**: Invalid Passkey
**Solution**:
- Sandbox: Get correct passkey from Developer Portal
- Production: Contact Safaricom support

### Debug Mode

Enable debug logging in `app/Services/MpesaService.php`:
```php
Log::info('M-Pesa Debug', ['data' => $payload]);
```

---

## API Endpoints

### STK Push (Deposits)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/mpesa/deposit` | POST | Initiate STK Push |
| `/mpesa/status` | POST | Check payment status |
| `/mpesa/callback` | POST | Receive Safaricom STK callbacks |

### B2C (Withdrawals)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/mpesa/withdraw` | POST | Initiate B2C withdrawal |
| `/mpesa/b2c-available` | GET | Check if B2C is configured |
| `/mpesa/b2c/result` | POST | Receive B2C result callbacks |
| `/mpesa/b2c/timeout` | POST | Receive B2C timeout callbacks |

---

## File Structure

```
laravel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── MpesaController.php    # M-Pesa controller (STK + B2C)
│   └── Services/
│       └── MpesaService.php           # M-Pesa service class
├── config/
│   └── mpesa.php                      # M-Pesa configuration
├── resources/
│   └── views/
│       ├── deposite.blade.php         # Deposit view with M-Pesa
│       └── withdraw.blade.php         # Withdraw view with M-Pesa B2C
├── routes/
│   └── web.php                        # M-Pesa routes added
├── public/
│   └── images/
│       └── Mpesa-Logo.png             # M-Pesa logo
└── .env.example                       # Environment template
```

---

## Support

For M-Pesa API support:
- **Safaricom Developer Portal**: https://developer.safaricom.co.ke/
- **Documentation**: https://developer.safaricom.co.ke/Documentation
- **Support Email**: apisupport@safaricom.co.ke

---

## Security Notes

⚠️ **Important Security Considerations**:

1. **Never commit `.env` to version control** - Contains sensitive credentials
2. **Use HTTPS for callbacks** - Required for production
3. **Validate callback data** - The controller validates callback authenticity
4. **Monitor transactions** - Regularly check for suspicious activity
5. **Keep credentials secure** - Rotate if compromised

---

## Quick Reference Card

```
┌─────────────────────────────────────────────────────────────┐
│              M-PESA QUICK REFERENCE                         │
├─────────────────────────────────────────────────────────────┤
│ Developer Portal: https://developer.safaricom.co.ke/       │
│                                                             │
│ ═══════════════ STK PUSH (DEPOSITS) ═══════════════        │
│                                                             │
│ SANDBOX:                                                    │
│   Shortcode: 174379                                         │
│   Test Phone: 254708374149                                  │
│                                                             │
│ ENV VARIABLES:                                              │
│   MPESA_ENVIRONMENT=sandbox                                 │
│   MPESA_CONSUMER_KEY=<from portal>                          │
│   MPESA_CONSUMER_SECRET=<from portal>                       │
│   MPESA_SHORTCODE=174379                                    │
│   MPESA_PASSKEY=<from portal>                               │
│   MPESA_CALLBACK_URL=https://yourdomain.com/mpesa/callback  │
│   MPESA_TRANSACTION_TYPE=CustomerPayBillOnline              │
│   MPESA_ACCOUNT_REFERENCE=Aviator                           │
│   MPESA_TRANSACTION_DESC=Deposit                            │
│                                                             │
│ ═══════════════ B2C (WITHDRAWALS) ══════════════════       │
│                                                             │
│ SANDBOX:                                                    │
│   Shortcode: 600000 (B2C)                                   │
│   Initiator: testapi                                        │
│                                                             │
│ ENV VARIABLES:                                              │
│   MPESA_B2C_INITIATOR_NAME=testapi                          │
│   MPESA_B2C_SECURITY_CREDENTIAL=<encrypted password>        │
│   MPESA_B2C_COMMAND_ID=BusinessPayment                      │
│   MPESA_B2C_RESULT_URL=https://yourdomain.com/mpesa/b2c/result│
│   MPESA_B2C_TIMEOUT_URL=https://yourdomain.com/mpesa/b2c/timeout│
│   MPESA_B2C_REMARKS=Aviator Withdrawal                      │
│                                                             │
│ ═══════════════════════════════════════════════════════    │
│                                                             │
│ CLEAR CACHE AFTER CHANGES:                                  │
│   php artisan config:clear                                  │
│   php artisan cache:clear                                   │
└─────────────────────────────────────────────────────────────┘
```
