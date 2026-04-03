/**
 * Rain/Giveaway System for Aviator Game
 */

class RainSystem {
    constructor() {
        this.activeRain = null;
        this.hasJoined = false;
        this.init();
    }

    init() {
        console.log('🌧️ Initializing Rain System...');
        
        // Rain cards are now part of chat messages, no need for polling
        // Just setup event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Join rain button
        $(document).on('click', '#join-rain-btn', () => {
            this.joinRain();
        });
        
        // Admin: Create rain button
        $(document).on('click', '#create-rain-btn', () => {
            this.showCreateRainModal();
        });
    }

    async checkActiveRain() {
        try {
            const response = await fetch('/rain/active');
            const data = await response.json();

            if (data.success && data.data) {
                this.activeRain = data.data.rain;
                // Banner removed - rain only shows in chat
            } else {
                this.activeRain = null;
                this.hasJoined = false;
            }
        } catch (error) {
            console.error('❌ Failed to check active rain:', error);
        }
    }

    joinRainFromChat() {
        this.joinRain();
    }
    
    async joinRain() {
        if (this.hasJoined) {
            toastr.error('You have already claimed this rain. Only one claim per user is allowed.');
            return;
        }

        try {
            const response = await fetch('/rain/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': hash_id,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                this.hasJoined = true;
                toastr.success(data.message);
                
                // UPDATE WALLET BALANCE IMMEDIATELY
                if (data.data && data.data.wallet_balance !== undefined) {
                    wallet_balance = data.data.wallet_balance.toString();
                    console.log('💰 Updated wallet_balance after claim:', wallet_balance);
                }
                if (data.data && data.data.freebet_balance !== undefined) {
                    freebet_balance = data.data.freebet_balance.toString();
                    console.log('🎁 Updated freebet_balance after claim:', freebet_balance);
                }
                
                // Update display based on current wallet type
                if (typeof window.updateWalletBalance === 'function') {
                    console.log('🔄 Calling updateWalletBalance() after claim...');
                    window.updateWalletBalance();
                } else {
                    console.warn('⚠️ window.updateWalletBalance() not found!');
                }
                
                // Show notification based on rain type
                if (data.data && data.data.is_admin_rain) {
                    // Admin/Support rain - instant freebet
                    toastr.success(`🎁 KSh ${data.data.amount_won} added to your Freebet wallet!`, 'Support Rain!', {timeOut: 4000});
                } else if (data.data && data.data.amount_won > 0) {
                    // User rain - instant cash
                    toastr.success(`💰 KSh ${data.data.amount_won} added to your cash wallet!`, 'Rain Claimed!');
                }
                
                // Immediate UI update - disable all claim buttons
                $('.rain-claim-btn').prop('disabled', true).text('Claiming...');
                
                // Force reload chat messages to get updated rain card
                if (typeof window.aviatorChat !== 'undefined') {
                    // Clear cache to force fresh data
                    window.aviatorChat.rainDataCache = {};
                    
                    // Reload messages with force update flag
                    window.aviatorChat.loadMessages(true).then(() => {
                        console.log('✅ Rain card updated after claim');
                    });
                }
            } else {
                toastr.error(data.message || 'Failed to join rain');
            }
        } catch (error) {
            console.error('❌ Failed to join rain:', error);
            toastr.error('Failed to join rain');
        }
    }

    showCreateRainModal() {
        // Check if modal already exists
        if ($('.rain-create-modal').length > 0) {
            return;
        }
        
        // ALWAYS use money wallet for creating rain (freebet cannot be used)
        let walletBalance = 0;
        if (typeof wallet_balance !== 'undefined') {
            walletBalance = parseFloat(wallet_balance.toString().replace(/,/g, '')) || 0;
        } else {
            // Fallback to DOM element (crash page)
            const walletText = $("#wallet_balance").text();
            walletBalance = parseFloat(walletText.replace(/[^0-9.]/g, '')) || 0;
        }
        console.log('💰 Rain modal - money balance:', walletBalance);
        
        // Add backdrop and modal
        const html = `
            <div class="rain-modal-backdrop" onclick="window.rainSystem.closeCreateRainModal()"></div>
            <div class="rain-create-modal">
                <h3>🌧️ Create Rain Giveaway</h3>
                <div class="rain-balance-info">
                    Money Wallet: KSh <span id="rain-user-balance">${walletBalance.toFixed(2)}</span>
                </div>
                <div class="rain-info-note" style="background: #1a1a2e; border-left: 3px solid #00d9ff; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 12px; color: #aaa;">
                    ℹ️ Only money wallet can be used to create rains. Freebet wallet cannot be used.
                </div>
                <div class="form-group">
                    <label>Amount Per User (KSh)</label>
                    <input type="number" id="rain-amount-per-user" min="1" max="${walletBalance}" value="100" class="form-control">
                </div>
                <div class="form-group">
                    <label>Number of Winners</label>
                    <input type="number" id="rain-num-winners" min="1" max="100" value="3" class="form-control">
                </div>
                <div class="rain-total">
                    Total: KSh <span id="rain-total-amount">300.00</span>
                </div>
                <div class="rain-warning" style="display: none; color: #ff0647; font-size: 12px; text-align: center; margin-top: 10px;">
                    Insufficient balance
                </div>
                <div class="form-actions">
                    <button onclick="window.rainSystem.createRain()" class="btn btn-success" id="rain-create-btn">Create Rain</button>
                    <button onclick="window.rainSystem.closeCreateRainModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        `;

        $('body').append(html);

        // Auto-calculate total and validate balance
        $('#rain-amount-per-user, #rain-num-winners').on('input', function() {
            const amount = parseFloat($('#rain-amount-per-user').val()) || 0;
            const winners = parseInt($('#rain-num-winners').val()) || 0;
            const total = amount * winners;
            
            // ALWAYS use money wallet balance (not freebet)
            let balance = 0;
            if (typeof wallet_balance !== 'undefined') {
                balance = parseFloat(wallet_balance.toString().replace(/,/g, '')) || 0;
            } else {
                balance = parseFloat($('#rain-user-balance').text()) || 0;
            }
            
            // Update displayed balance in modal
            $('#rain-user-balance').text(balance.toFixed(2));
            
            $('#rain-total-amount').text(total.toFixed(2));
            
            console.log('🧮 Rain calculation - Amount:', amount, 'Winners:', winners, 'Total:', total, 'Balance:', balance);
            
            // Check if user has enough balance
            if (total > balance) {
                $('.rain-warning').show();
                $('#rain-create-btn').prop('disabled', true).css('opacity', '0.5');
            } else {
                $('.rain-warning').hide();
                $('#rain-create-btn').prop('disabled', false).css('opacity', '1');
            }
        });
    }
    
    closeCreateRainModal() {
        $('.rain-modal-backdrop').remove();
        $('.rain-create-modal').remove();
    }

    async createRain() {
        const amountPerUser = parseFloat($('#rain-amount-per-user').val());
        const numWinners = parseInt($('#rain-num-winners').val());

        if (!amountPerUser || !numWinners) {
            toastr.error('Please fill in all fields');
            return;
        }

        // ALWAYS use money wallet (freebet cannot be used for creating rain)
        const walletType = 'money';
        console.log('🎯 Creating rain with wallet type:', walletType);

        try {
            const response = await fetch('/rain/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': hash_id,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    amount_per_user: amountPerUser,
                    num_winners: numWinners,
                    wallet_type: walletType
                })
            });

            const data = await response.json();

            if (data.success) {
                toastr.success('Rain created successfully!');
                
                // UPDATE BOTH WALLET BALANCES IMMEDIATELY (deducted amount)
                if (data.wallet_balance !== undefined) {
                    wallet_balance = data.wallet_balance.toString();
                    console.log('💰 Updated wallet_balance after create:', wallet_balance);
                }
                if (data.freebet_balance !== undefined) {
                    freebet_balance = data.freebet_balance.toString();
                    console.log('🎁 Updated freebet_balance after create:', freebet_balance);
                }
                
                // Update display based on current wallet type
                if (typeof window.updateWalletBalance === 'function') {
                    console.log('🔄 Calling updateWalletBalance() after create...');
                    window.updateWalletBalance();
                } else {
                    console.warn('⚠️ window.updateWalletBalance() not found!');
                }
                
                this.closeCreateRainModal();
                this.checkActiveRain();
            } else {
                toastr.error(data.message || 'Failed to create rain');
            }
        } catch (error) {
            console.error('❌ Failed to create rain:', error);
            toastr.error('Failed to create rain');
        }
    }
}

// Initialize rain system when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.rainSystem = new RainSystem();
});
