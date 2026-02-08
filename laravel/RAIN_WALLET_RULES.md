# Rain Wallet Distribution Rules

## Admin/Support Rains → Freebet Wallet
- **Rain Source**: Created by admin/support
- **Wallet Type**: Freebet Wallet
- **Wagering Required**: Yes
- **Withdrawal**: Only after meeting wagering requirements
- **Usage**: Can be used for betting with wagering rules applied

## User Rains → Real Cash Wallet
- **Rain Source**: Created by regular users (using their own balance)
- **Wallet Type**: Real Cash Wallet
- **Wagering Required**: No
- **Withdrawal**: Immediate (no wagering needed)
- **Usage**: Same as deposited money - can bet or withdraw immediately

## Implementation Details

### Database
- `wallets.amount` → Real cash balance
- `wallets.freebet_amount` → Freebet balance (admin rains)

### Distribution Logic
```php
if ($isAdminRain) {
    // Add to freebet_amount column
    addFreebetWallet($userId, $amount);
} else {
    // Add to amount column (real cash)
    addwallet($userId, $amount, "+");
}
```

### Rain Creation
- **Admin creates rain**: Money comes from system (free)
- **User creates rain**: Money deducted from user's real cash wallet

### Winner Receives
- **Admin rain winner**: Gets freebet (wagering required)
- **User rain winner**: Gets real cash (no wagering)

## Wagering Requirements (Future Implementation)
- Freebet wagering multiplier: TBD (e.g., 1x, 3x, 5x)
- Track freebet usage and wagering progress
- Convert freebets to cash after wagering complete
