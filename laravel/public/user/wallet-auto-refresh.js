/**
 * Auto Wallet Balance Refresh
 * 
 * This script periodically checks for wallet balance updates from the server
 * and updates the UI immediately when changes are detected.
 * 
 * Use case: When admin adds/removes freebet, user sees the change immediately
 * without waiting for next game round or page refresh.
 */

(function() {
    // Configuration
    const CHECK_INTERVAL = 3000; // Check every 3 seconds
    let previousMoneyBalance = null;
    let previousFreebetBalance = null;
    let isChecking = false;
    
    /**
     * Check for wallet balance changes
     */
    function checkWalletUpdates() {
        // Prevent concurrent checks
        if (isChecking) {
            return;
        }
        
        isChecking = true;
        
        $.ajax({
            url: '/get_user_details',
            type: 'GET',
            dataType: 'json',
            success: function(result) {
                if (result && result.data) {
                    const newMoneyBalance = parseFloat(result.data.wallet) || 0;
                    const newFreebetBalance = parseFloat(result.data.freebet) || 0;
                    
                    // Initialize on first check
                    if (previousMoneyBalance === null) {
                        previousMoneyBalance = newMoneyBalance;
                        previousFreebetBalance = newFreebetBalance;
                        console.log('💰 Wallet auto-refresh initialized - Money:', newMoneyBalance, 'Freebet:', newFreebetBalance);
                        isChecking = false;
                        return;
                    }
                    
                    // Check for changes
                    let hasChanges = false;
                    
                    if (newMoneyBalance !== previousMoneyBalance) {
                        const diff = newMoneyBalance - previousMoneyBalance;
                        console.log('💵 Money wallet changed:', diff > 0 ? '+' : '', diff.toFixed(2), 'KSh (New:', newMoneyBalance, ')');
                        hasChanges = true;
                    }
                    
                    if (newFreebetBalance !== previousFreebetBalance) {
                        const diff = newFreebetBalance - previousFreebetBalance;
                        console.log('🎁 Freebet wallet changed:', diff > 0 ? '+' : '', diff.toFixed(2), 'KSh (New:', newFreebetBalance, ')');
                        hasChanges = true;
                    }
                    
                    if (hasChanges) {
                        // Update global variables
                        if (typeof wallet_balance !== 'undefined') {
                            wallet_balance = newMoneyBalance;
                        }
                        if (typeof freebet_balance !== 'undefined') {
                            freebet_balance = newFreebetBalance;
                        }
                        
                        // Update UI
                        updateWalletDisplay(newMoneyBalance, newFreebetBalance);
                        
                        // Show notification
                        showWalletChangeNotification(
                            newMoneyBalance - previousMoneyBalance,
                            newFreebetBalance - previousFreebetBalance
                        );
                        
                        // Update previous values
                        previousMoneyBalance = newMoneyBalance;
                        previousFreebetBalance = newFreebetBalance;
                    }
                }
                
                isChecking = false;
            },
            error: function(xhr, status, error) {
                console.error('❌ Wallet check failed:', error);
                isChecking = false;
            }
        });
    }
    
    /**
     * Update wallet display in UI
     */
    function updateWalletDisplay(moneyBalance, freebetBalance) {
        // Determine which wallet to display based on current selection
        const currentWalletType = typeof current_wallet_type !== 'undefined' ? current_wallet_type : 'money';
        const displayBalance = (currentWalletType === 'freebet') ? freebetBalance : moneyBalance;
        const currencySymbol = typeof currency_symbol !== 'undefined' ? currency_symbol : 'KSh ';
        
        // Format balance
        const formattedBalance = displayBalance.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Update all wallet display elements
        $('#wallet_balance').html(formattedBalance);
        $('#header_wallet_balance').html(currencySymbol + formattedBalance);
        
        // Update wallet balance spans if they exist
        $('.wallet-balance span').html(currencySymbol + formattedBalance);
        
        console.log('✅ Wallet display updated - Showing:', currentWalletType, '=', formattedBalance);
    }
    
    /**
     * Show notification when wallet changes
     */
    function showWalletChangeNotification(moneyDiff, freebetDiff) {
        if (typeof toastr === 'undefined') {
            return;
        }
        
        if (moneyDiff !== 0) {
            const message = moneyDiff > 0 
                ? `+${moneyDiff.toFixed(2)} KSh added to your money wallet!`
                : `${moneyDiff.toFixed(2)} KSh deducted from your money wallet`;
            
            if (moneyDiff > 0) {
                toastr.success(message, 'Wallet Updated', {
                    timeOut: 5000,
                    progressBar: true
                });
            } else {
                toastr.info(message, 'Wallet Updated', {
                    timeOut: 5000,
                    progressBar: true
                });
            }
        }
        
        if (freebetDiff !== 0) {
            const message = freebetDiff > 0 
                ? `+${freebetDiff.toFixed(2)} KSh freebet added to your account!`
                : `${freebetDiff.toFixed(2)} KSh freebet deducted`;
            
            if (freebetDiff > 0) {
                toastr.success(message, 'Freebet Updated', {
                    timeOut: 5000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                });
            } else {
                toastr.info(message, 'Freebet Updated', {
                    timeOut: 5000,
                    progressBar: true
                });
            }
        }
    }
    
    /**
     * Initialize auto-refresh
     */
    function initWalletAutoRefresh() {
        // Check if jQuery is available
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded - wallet auto-refresh disabled');
            return;
        }
        
        console.log('🔄 Wallet auto-refresh started (checking every', CHECK_INTERVAL / 1000, 'seconds)');
        
        // Initial check
        checkWalletUpdates();
        
        // Set up interval
        setInterval(checkWalletUpdates, CHECK_INTERVAL);
    }
    
    // Initialize when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWalletAutoRefresh);
    } else {
        initWalletAutoRefresh();
    }
    
    // Expose function globally for manual refresh
    window.refreshWalletBalance = checkWalletUpdates;
    
})();
