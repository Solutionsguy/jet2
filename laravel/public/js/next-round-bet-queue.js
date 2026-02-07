/**
 * ========================================================
 * NEXT ROUND BET QUEUE SYSTEM
 * ========================================================
 * 
 * Allows users to place bets during FLYING phase that will
 * be automatically placed in the NEXT round.
 * 
 * Features:
 * - Queue bets during FLYING/CRASHED phase
 * - Auto-place when WAITING phase starts
 * - Visual indicators (orange badge, "Next Round" text)
 * - Cancel queued bets
 * - No bet clearing on crash
 * 
 * Usage:
 * 1. Include this file AFTER aviatorold.js
 * 2. Queue system activates automatically
 * ========================================================
 */

// Global queue for next round bets
window.next_round_bet_queue = [];

// Track current game phase
window.currentGamePhase = 'unknown';

/**
 * Check if we're in a phase where bets should be queued for next round
 * @returns {string} 'place_now', 'queue_next', or 'blocked'
 */
window.getBetPlacementMode = function() {
    const currentPhase = window.currentGamePhase || 'unknown';
    
    console.log('🔍 Current game phase:', currentPhase);
    
    // WAITING phase: Place bets immediately
    if (currentPhase === 'waiting') {
        return 'place_now';
    }
    
    // COUNTDOWN phase: Place bets immediately (last chance)
    if (currentPhase === 'countdown') {
        return 'place_now';
    }
    
    // FLYING phase: Queue for next round
    if (currentPhase === 'flying') {
        return 'queue_next';
    }
    
    // CRASHED phase: Queue for next round (waiting phase coming soon)
    if (currentPhase === 'crashed') {
        return 'queue_next';
    }
    
    // Unknown phase: Default to queuing (safer)
    return 'queue_next';
};

/**
 * Process queued bets when WAITING phase starts
 * Moves bets from next_round_bet_queue to bet_array
 */
window.processNextRoundBetQueue = function() {
    console.log('🎯 Processing next round bet queue:', window.next_round_bet_queue.length, 'bet(s)');
    
    if (window.next_round_bet_queue.length === 0) {
        console.log('📋 No queued bets to process');
        return;
    }
    
    // Move queued bets to bet_array for placement
    window.next_round_bet_queue.forEach(queuedBet => {
        console.log('📋 Moving queued bet to bet_array:', queuedBet);
        
        // Check if this section already has a bet in bet_array
        const existingBetIndex = bet_array.findIndex(b => b.section_no === queuedBet.section_no);
        
        if (existingBetIndex >= 0) {
            // Update existing bet
            bet_array[existingBetIndex] = {
                bet_type: queuedBet.bet_type,
                bet_amount: queuedBet.bet_amount,
                section_no: queuedBet.section_no
            };
        } else {
            // Add new bet
            bet_array.push({
                bet_type: queuedBet.bet_type,
                bet_amount: queuedBet.bet_amount,
                section_no: queuedBet.section_no
            });
        }
        
        // Send bet intent for queued bets (they are first placers!)
        if (typeof sendBetIntent === 'function') {
            sendBetIntent(queuedBet.bet_amount, true); // isAutoBet = true (first placer priority)
        }
        
        // Update UI - change from "Next Round" to "Waiting..."
        const sectionId = queuedBet.section_no == 0 ? '#main_bet_section' : '#extra_bet_section';
        $(sectionId).find("#cancle_button #waiting").text('Waiting...');
        $(sectionId + " .controls").addClass('bet-border-red');
    });
    
    // Clear the queue (bets are now in bet_array)
    window.next_round_bet_queue = [];
    
    console.log('✅ Queued bets moved to bet_array:', bet_array);
};

/**
 * Add bet to next round queue
 * @param {number} bet_type - 0 for normal, 1 for auto
 * @param {number} bet_amount - Bet amount
 * @param {number} section_no - 0 for main, 1 for extra
 */
window.queueBetForNextRound = function(bet_type, bet_amount, section_no) {
    console.log('📋 Queueing bet for NEXT round:', { bet_type, bet_amount, section_no });
    
    // Check if already queued for this section
    const existingQueueIndex = window.next_round_bet_queue.findIndex(b => b.section_no === section_no);
    
    if (existingQueueIndex >= 0) {
        // Update existing queued bet
        window.next_round_bet_queue[existingQueueIndex].bet_amount = bet_amount;
        window.next_round_bet_queue[existingQueueIndex].bet_type = bet_type;
        if (typeof toastr !== 'undefined') {
            toastr.info('Bet updated for next round');
        }
    } else {
        // Add new queued bet
        window.next_round_bet_queue.push({
            bet_type: bet_type,
            bet_amount: bet_amount,
            section_no: section_no,
            queued_at: Date.now()
        });
    }
    
    // Update UI to show "queued" state
    const sectionId = section_no == 0 ? '#main_bet_section' : '#extra_bet_section';
    const prefix = section_no == 0 ? 'main' : 'extra';
    
    $(sectionId).find("#bet_button").hide();
    $(sectionId).find("#cancle_button").show();
    $(sectionId).find("#cancle_button #waiting").show();
    $(sectionId + " .controls").removeClass('bet-border-yellow bet-border-red');
    
    // Disable controls
    $("." + prefix + "_bet_amount").prop('disabled', true);
    $("#" + prefix + "_plus_btn").prop('disabled', true);
    $("#" + prefix + "_minus_btn").prop('disabled', true);
    $("." + prefix + "_amount_btn").prop('disabled', true);
    
    console.log('✅ Bet queued. Total queued bets:', window.next_round_bet_queue.length);
    console.log('📋 Queue contents:', window.next_round_bet_queue);
};

/**
 * Remove bet from next round queue
 * @param {number} section_no - 0 for main, 1 for extra
 */
window.removeFromNextRoundQueue = function(section_no) {
    const queueIndex = window.next_round_bet_queue.findIndex(b => b.section_no === section_no);
    
    if (queueIndex >= 0) {
        window.next_round_bet_queue.splice(queueIndex, 1);
        console.log('🗑️ Removed queued bet for section:', section_no);
        
        // Remove visual indicators
        const sectionId = section_no == 0 ? '#main_bet_section' : '#extra_bet_section';
        $(sectionId).find(".next-round-badge").remove();
        return true;
    }
    
    return false;
};

/**
 * Check if a section has a queued bet
 * @param {number} section_no - 0 for main, 1 for extra
 * @returns {boolean}
 */
window.hasQueuedBet = function(section_no) {
    return window.next_round_bet_queue.some(b => b.section_no === section_no);
};

/**
 * Get queued bet for a section
 * @param {number} section_no - 0 for main, 1 for extra
 * @returns {object|null}
 */
window.getQueuedBet = function(section_no) {
    return window.next_round_bet_queue.find(b => b.section_no === section_no) || null;
};

/**
 * Clear all queued bets (use with caution)
 */
window.clearNextRoundQueue = function() {
    console.log('🗑️ Clearing all queued bets');
    window.next_round_bet_queue = [];
    $('.next-round-badge').remove();
    $('.bet-border-orange').removeClass('bet-border-orange');
};

// ========== INITIALIZATION ==========
$(document).ready(function() {
    console.log('✅ Next Round Bet Queue System initialized');
    console.log('📋 Queue:', window.next_round_bet_queue);
});
