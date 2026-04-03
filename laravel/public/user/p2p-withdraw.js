/**
 * P2P Withdrawal JavaScript
 * Handles the interactive P2P matching experience
 */

let p2pPollInterval = null;
let p2pCurrentRef = null;

function startP2PSearch() {
    const amount = $('#p2p_withdraw_amount').val();
    
    if (!amount || amount <= 0) {
        toastr.error("Please enter a valid amount.");
        return;
    }

    // Show searching UI
    $('#p2p_initial_form').hide();
    $('#p2p_searching_area').show();
    
    // Start rotating status messages
    startStatusRotation();

    // Call API to initiate
    $.ajax({
        url: '/p2p/search',
        method: 'POST',
        data: {
            amount: amount,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.isSuccess) {
                p2pCurrentRef = response.reference;
                // Start polling for match
                p2pPollInterval = setInterval(checkMatchStatus, 3000);
            } else {
                showP2PError(response.message);
            }
        },
        error: function(xhr) {
            const msg = xhr.responseJSON ? xhr.responseJSON.message : "Connection error";
            showP2PError(msg);
        }
    });
}

function checkMatchStatus() {
    if (!p2pCurrentRef) return;

    $.get('/p2p/status/' + p2pCurrentRef, function(response) {
        if (response.status === 'matched') {
            clearInterval(p2pPollInterval);
            showMatchedPeer(response.peer);
        } else if (response.status === 'failed' || response.status === 'cancelled') {
            clearInterval(p2pPollInterval);
            showP2PError("Transaction " + response.status);
        }
    });
}

function showMatchedPeer(peer) {
    $('#p2p_searching_area').hide();
    $('#p2p_matched_area').show();
    
    // Play success sound if needed
    // new Audio('/sounds/match-found.mp3').play();

    $('#matched_peer_name').text("Matched: " + peer.name);
    $('#matched_peer_phone').text(peer.phone);
    $('#matched_peer_rate').text(peer.success_rate + "%");
    $('#matched_peer_time').text(peer.avg_time);
    
    toastr.success("Peer matched successfully!");
}

function cancelP2PSearch() {
    if (!p2pCurrentRef) {
        resetP2P();
        return;
    }

    if (!confirm("Are you sure you want to cancel the search? Your wallet will be refunded.")) return;

    $.post('/p2p/cancel/' + p2pCurrentRef, {
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function(response) {
        clearInterval(p2pPollInterval);
        toastr.info(response.message);
        resetP2P();
    });
}

function resetP2P() {
    clearInterval(p2pPollInterval);
    p2pCurrentRef = null;
    $('#p2p_searching_area').hide();
    $('#p2p_matched_area').hide();
    $('#p2p_initial_form').show();
}

function showP2PError(message) {
    toastr.error(message);
    resetP2P();
}

function startStatusRotation() {
    const statuses = [
        "Scanning for active peers...",
        "Connecting to liquidity network...",
        "Verifying peer credentials...",
        "Checking regional nodes...",
        "Finalizing secure bridge...",
        "Optimizing for fast settlement..."
    ];
    
    let i = 0;
    const interval = setInterval(() => {
        if ($('#p2p_searching_area').is(':visible')) {
            $('#p2p_status_text').text(statuses[i]);
            i = (i + 1) % statuses.length;
        } else {
            clearInterval(interval);
        }
    }, 2500);
}
