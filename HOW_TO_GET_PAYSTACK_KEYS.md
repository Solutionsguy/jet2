# How to Get Your Paystack API Keys

## Step 1: Create Paystack Account

1. **Go to Paystack Website**
   - Visit: https://dashboard.paystack.com/signup
   - Or if you have account: https://dashboard.paystack.com/login

2. **Sign Up**
   - Enter your email address
   - Create a password
   - Click "Sign Up"

3. **Verify Email**
   - Check your email inbox
   - Click the verification link
   - Complete your profile

## Step 2: Get Your API Keys

1. **Login to Dashboard**
   - Go to: https://dashboard.paystack.com

2. **Navigate to API Keys**
   - Click on **Settings** (gear icon) in the sidebar
   - Click **API Keys & Webhooks**

3. **Copy Your Keys**
   
   You'll see two sections:

   **TEST KEYS** (For Development):
   ```
   Test Public Key:  pk_test_xxxxxxxxxxxxxxxxxxxxxxxx
   Test Secret Key:  sk_test_xxxxxxxxxxxxxxxxxxxxxxxx
   ```
   
   **LIVE KEYS** (For Production - After KYC):
   ```
   Live Public Key:  pk_live_xxxxxxxxxxxxxxxxxxxxxxxx
   Live Secret Key:  sk_live_xxxxxxxxxxxxxxxxxxxxxxxx
   ```

4. **Copy the TEST Keys First**
   - Click the "eye" icon to reveal the Secret Key
   - Click "Copy" button next to each key
   - Save them somewhere safe temporarily

## Step 3: Add Keys to Your .env File

1. **Open `.env` File**
   - Located in: `laravel/.env`
   - Use any text editor

2. **Find the Paystack Section**
   - Look for lines starting with `PAYSTACK_`
   - Should look like this:
   ```env
   PAYSTACK_PUBLIC_KEY=pk_test_your_public_key_here
   PAYSTACK_SECRET_KEY=sk_test_your_secret_key_here
   ```

3. **Replace with Your Actual Keys**
   ```env
   # Replace these with the keys you copied
   PAYSTACK_PUBLIC_KEY=pk_test_abc123xyz789...
   PAYSTACK_SECRET_KEY=sk_test_xyz789abc123...
   ```

4. **Save the File**

## Step 4: Clear Laravel Cache

Open terminal/command prompt and run:

```bash
# Navigate to your laravel folder
cd laravel

# Clear configuration cache
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear
```

## Step 5: Verify It Works

1. **Check if keys are loaded:**
   ```bash
   php artisan tinker
   ```
   
   Then type:
   ```php
   config('paystack.public_key')
   config('paystack.secret_key')
   ```
   
   You should see your keys displayed.
   
   Type `exit` to quit tinker.

2. **Test the deposit page:**
   - Start server: `php artisan serve`
   - Go to: `http://127.0.0.1:8000/deposit`
   - If configured correctly, you won't see the error

## Quick Reference

### Where to Find Things:

| Item | Location |
|------|----------|
| Paystack Dashboard | https://dashboard.paystack.com |
| API Keys Page | Settings → API Keys & Webhooks |
| Your .env file | `laravel/.env` |
| Test Keys | Use these for development |
| Live Keys | Use after KYC approval only |

### Example of Correct .env Setup:

```env
# Paystack Configuration
PAYSTACK_PUBLIC_KEY=pk_test_0123456789abcdefghijklmnopqrstuvwxyz
PAYSTACK_SECRET_KEY=sk_test_0123456789abcdefghijklmnopqrstuvwxyz
PAYSTACK_CALLBACK_URL=http://127.0.0.1:8000/paystack/callback
PAYSTACK_MERCHANT_EMAIL=yourname@youremail.com
PAYSTACK_CURRENCY=KES
PAYSTACK_CURRENCY_SYMBOL=KSh
PAYSTACK_MIN_DEPOSIT=50
PAYSTACK_MIN_WITHDRAWAL=100
```

## Common Mistakes to Avoid

❌ **Don't** use the example keys (`pk_test_your_public_key_here`)
❌ **Don't** add quotes around the keys
❌ **Don't** add spaces before or after the = sign
❌ **Don't** share your secret key publicly
❌ **Don't** commit .env to git

✅ **Do** copy the exact key from Paystack dashboard
✅ **Do** clear cache after adding keys
✅ **Do** use TEST keys for development
✅ **Do** keep your secret key private

## Troubleshooting

### "Paystack is not configured" Error

**Cause**: Keys not in .env or cache not cleared

**Solution**:
1. Open `laravel/.env`
2. Make sure these lines exist:
   ```env
   PAYSTACK_PUBLIC_KEY=pk_test_your_actual_key
   PAYSTACK_SECRET_KEY=sk_test_your_actual_key
   ```
3. Run: `php artisan config:clear`
4. Restart server: `php artisan serve`

### Keys Not Loading

**Solution**:
```bash
# 1. Check .env file exists
ls laravel/.env

# 2. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Restart server
php artisan serve
```

### Still Not Working?

Check if APP_KEY is set in .env:
```bash
# If APP_KEY is empty in .env, generate it:
php artisan key:generate
```

## Next Steps After Adding Keys

1. ✅ Keys added to .env
2. ✅ Cache cleared
3. ✅ Test deposit page works
4. 🚀 Ready to test payments!

---

**Need Help?**
- Paystack Support: support@paystack.com
- Paystack Docs: https://paystack.com/docs
