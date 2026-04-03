# FREEBET RAIN - TROUBLESHOOTING GUIDE

## What Was Implemented

### Backend (RainController.php)
✅ Accepts 'wallet_type' parameter (money/freebet)
✅ Validates wallet_type
✅ Deducts from correct wallet when creating rain
✅ Returns both wallet_balance and freebet_balance
✅ Admin rains add to freebet wallet
✅ User rains add to money wallet
✅ Comprehensive logging added

### Frontend (rain.js)
✅ Sends wallet_type in create rain request
✅ Updates both wallet_balance and freebet_balance variables
✅ Calls updateWalletBalance() to refresh display
✅ Shows correct balance in rain modal

### Display (crash.blade.php)
✅ updateWalletBalance() is globally accessible
✅ Updates display based on current_wallet_type

## How It Should Work

### Creating Rain
1. User toggles to Freebet wallet (current_wallet_type = 'freebet')
2. User clicks to create rain
3. Frontend sends wallet_type: 'freebet' to backend
4. Backend checks freebet balance
5. Backend deducts from freebet_amount
6. Backend returns new balances
7. Frontend updates freebet_balance variable
8. Frontend calls updateWalletBalance()
9. Display refreshes showing new freebet balance

### Claiming Rain
1. User claims rain
2. Backend checks if rain creator is admin
3. If admin: Adds to freebet_amount
4. If user: Adds to amount (money)
5. Returns both balances
6. Frontend updates both variables
7. Display refreshes

## Debugging Steps

### 1. Check if wallet_type is being sent
Open browser console, try to create rain, look for:
"🎯 Creating rain with wallet type: freebet"

### 2. Check backend logs
File: storage/logs/laravel.log
Look for: "Rain creation request - User: X, WalletType: freebet"

### 3. Check if balances are returned
Console should show:
"💰 Updated wallet_balance after create: X"
"🎁 Updated freebet_balance after create: Y"

### 4. Check if display updates
Console should show:
"🔄 Calling updateWalletBalance() after create..."

## Test Page
URL: /tmp_rovodev_test_rain_wallets.html
This page helps test the wallet switching and rain creation

## Common Issues

### Issue: Balance not updating in display
- Check if updateWalletBalance() exists globally
- Check if current_wallet_type matches the wallet you're using
- Check browser console for errors

### Issue: Wrong wallet being deducted
- Check console for wallet_type being sent
- Verify current_wallet_type variable is set correctly

### Issue: Claims not adding to freebet
- Check logs to see if rain creator is admin
- Verify addFreebetWallet() is being called
- Check database directly to confirm

Generated: 2026-02-20 05:10:55
