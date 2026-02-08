/**
 * Admin Freebet Management JavaScript
 */

// Add Freebet to User
function addFreebet() {
    console.log('addFreebet called');
    
    const btn = document.getElementById('add-freebet-btn');
    if (!btn) {
        alert('Button not found!');
        return;
    }
    
    const userIdentifier = document.getElementById('add-user-identifier').value;
    const amount = document.getElementById('add-amount').value;
    const reason = document.getElementById('add-reason').value;
    
    if (!userIdentifier || !amount) {
        alert('Please fill in all required fields');
        return;
    }
    
    if (parseFloat(amount) < 1) {
        alert('Amount must be at least KSh 1');
        return;
    }
    
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Adding...';
    
    console.log('Sending add freebet request:', {
        user_identifier: userIdentifier,
        amount: amount,
        reason: reason
    });
    
    // Force reset after 3 seconds if nothing happens
    const resetTimeout = setTimeout(() => {
        console.warn('ADD FREEBET TIMEOUT - forcing button reset');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Add Freebet: Request timed out. Check console for details.');
    }, 3000);
    
    // Check if jQuery is available
    if (typeof $ === 'undefined') {
        clearTimeout(resetTimeout);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('jQuery not loaded! Cannot send request.');
        return;
    }
    
    $.ajax({
        url: '/admin/freebet/add',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            user_identifier: userIdentifier,
            amount: amount,
            reason: reason
        },
        success: function(response) {
            clearTimeout(resetTimeout);
            console.log('Add freebet response:', response);
            
            if (response.success) {
                alert(response.message || 'Freebet added successfully!');
                $('#addFreebetModal').modal('hide');
                document.getElementById('addFreebetForm').reset();
                
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Failed: ' + (response.message || 'Failed to add freebet'));
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },
        error: function(xhr, status, error) {
            clearTimeout(resetTimeout);
            console.error('Add freebet error:', xhr, status, error);
            
            let errorMsg = 'Server error';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMsg = 'Error: ' + xhr.statusText;
            } else if (status === 'timeout') {
                errorMsg = 'Request timeout. Please try again.';
            }
            
            alert('Error: ' + errorMsg);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        },
        complete: function(xhr, status) {
            clearTimeout(resetTimeout);
            console.log('Add freebet request complete. Status:', status);
            // Only reset if not already handled in success/error
            setTimeout(() => {
                if (btn.disabled) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="mdi mdi-plus-circle"></i> Add Freebet';
                }
            }, 100);
        }
    });
}

// Remove Freebet from User
function removeFreebet() {
    console.log('removeFreebet called');
    
    const btn = document.getElementById('remove-freebet-btn');
    if (!btn) {
        alert('Button not found!');
        return;
    }
    
    const userIdentifier = document.getElementById('remove-user-identifier').value;
    const amount = document.getElementById('remove-amount').value;
    const reason = document.getElementById('remove-reason').value;
    
    if (!userIdentifier || !amount || !reason) {
        toastr.error('Please fill in all required fields');
        return;
    }
    
    if (!confirm(`⚠️ Are you sure you want to REMOVE KSh ${amount} freebet from this user?\n\nReason: ${reason}\n\nThis action cannot be undone!`)) {
        console.log('User cancelled');
        return;
    }
    
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Removing...';
    
    console.log('Sending remove freebet request:', {
        user_identifier: userIdentifier,
        amount: amount,
        reason: reason
    });
    
    // Force reset after 3 seconds if nothing happens
    const resetTimeout = setTimeout(() => {
        console.warn('Timeout - forcing button reset');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        toastr.error('Request timed out or no response received');
    }, 3000);
    
    $.ajax({
        url: '/admin/freebet/remove',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            user_identifier: userIdentifier,
            amount: amount,
            reason: reason
        },
        success: function(response) {
            clearTimeout(resetTimeout);
            console.log('Remove freebet response:', response);
            
            if (response.success) {
                alert(response.message || 'Freebet removed successfully!');
                $('#removeFreebetModal').modal('hide');
                document.getElementById('removeFreebetForm').reset();
                
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Failed: ' + (response.message || 'Failed to remove freebet'));
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },
        error: function(xhr, status, error) {
            clearTimeout(resetTimeout);
            console.error('Remove freebet error:', xhr, status, error);
            
            let errorMsg = 'Server error';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMsg = 'Error: ' + xhr.statusText;
            } else if (status === 'timeout') {
                errorMsg = 'Request timeout. Please try again.';
            }
            
            alert('Error: ' + errorMsg);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        },
        complete: function(xhr, status) {
            clearTimeout(resetTimeout);
            console.log('Remove freebet request complete. Status:', status);
            setTimeout(() => {
                if (btn.disabled) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="mdi mdi-minus-circle"></i> Remove Freebet';
                }
            }, 100);
        }
    });
}

// Bulk Add Freebet
function bulkAddFreebet() {
    console.log('bulkAddFreebet called');
    
    const btn = document.getElementById('bulk-freebet-btn');
    if (!btn) {
        alert('Button not found!');
        return;
    }
    
    const method = document.getElementById('bulk-method').value;
    const amount = document.getElementById('bulk-amount').value;
    const reason = document.getElementById('bulk-reason').value;
    
    let userIds = [];
    let confirmMessage = '';
    
    if (method === 'manual') {
        const userIdsInput = document.getElementById('bulk-user-ids').value;
        if (!userIdsInput) {
            alert('Please enter user IDs');
            return;
        }
        userIds = userIdsInput.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
        
        if (userIds.length === 0) {
            alert('Please enter valid user IDs');
            return;
        }
        
        const totalCost = userIds.length * parseFloat(amount);
        confirmMessage = `🎁 Bulk Freebet Distribution\n\n• Method: Manual Selection\n• Users: ${userIds.length}\n• Amount per user: KSh ${amount}\n• Total cost: KSh ${totalCost.toFixed(2)}\n\nProceed?`;
    } else if (method === 'all') {
        confirmMessage = `🎁 Bulk Freebet Distribution\n\n⚠️ WARNING: This will distribute to ALL USERS!\n\n• Method: All Users\n• Amount per user: KSh ${amount}\n\nThis may be expensive! Proceed?`;
    } else if (method === 'active') {
        confirmMessage = `🎁 Bulk Freebet Distribution\n\n• Method: Active Users (Last 7 Days)\n• Amount per user: KSh ${amount}\n\nProceed?`;
    }
    
    if (!amount) {
        alert('Please enter amount');
        return;
    }
    
    if (!confirm(confirmMessage)) {
        console.log('User cancelled');
        return;
    }
    
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Distributing...';
    
    console.log('Sending bulk freebet request:', {
        user_ids: userIds,
        amount: amount,
        reason: reason || 'Bulk distribution'
    });
    
    // Force reset after 3 seconds if nothing happens
    const resetTimeout = setTimeout(() => {
        console.warn('Timeout - forcing button reset');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        toastr.error('Request timed out or no response received');
    }, 3000);
    
    // Check if jQuery is available
    if (typeof $ === 'undefined') {
        clearTimeout(resetTimeout);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('jQuery not loaded! Cannot send request.');
        return;
    }
    
    // Prepare data based on method
    let ajaxData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        method: method,
        amount: amount,
        reason: reason || 'Bulk distribution'
    };
    
    // Only add user_ids for manual selection
    if (method === 'manual') {
        ajaxData.user_ids = userIds;
    }
    
    $.ajax({
        url: '/admin/freebet/bulk',
        type: 'POST',
        data: ajaxData,
        success: function(response) {
            clearTimeout(resetTimeout);
            console.log('Bulk freebet response:', response);
            
            if (response.success) {
                const data = response.data;
                let message = `Successfully distributed to ${data.success_count} users! Total: KSh ${data.total_amount.toFixed(2)}`;
                
                if (data.failed_count > 0) {
                    message += `\n\nWarning: ${data.failed_count} users failed.`;
                }
                
                alert(message);
                $('#bulkFreebetModal').modal('hide');
                document.getElementById('bulkFreebetForm').reset();
                updateBulkPreview();
                
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Failed: ' + (response.message || 'Bulk operation failed'));
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },
        error: function(xhr, status, error) {
            clearTimeout(resetTimeout);
            console.error('Bulk freebet error:', xhr, status, error);
            
            let errorMsg = 'Server error';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.statusText) {
                errorMsg = 'Error: ' + xhr.statusText;
            } else if (status === 'timeout') {
                errorMsg = 'Request timeout. Please try again.';
            }
            
            toastr.error(errorMsg, 'Bulk Distribution Failed', {timeOut: 5000});
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        },
        complete: function(xhr, status) {
            clearTimeout(resetTimeout);
            console.log('Bulk freebet request complete. Status:', status);
            // Only reset if not already handled in success/error
            setTimeout(() => {
                if (btn.disabled) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="mdi mdi-account-multiple-plus"></i> Distribute';
                }
            }, 100);
        }
    });
}

// View User Freebet History
function viewUserHistory(userId) {
    $('#userHistoryModal').modal('show');
    
    $.ajax({
        url: '/admin/freebet/user/' + userId + '/history',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const user = response.data.user;
                const transactions = response.data.transactions;
                
                // Update user info
                document.getElementById('history-username').textContent = user.username;
                document.getElementById('history-user-id').textContent = user.id;
                document.getElementById('history-current-balance').textContent = user.current_freebet.toFixed(2);
                
                // Update transactions table
                let html = '';
                if (transactions.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted">No transactions found</td></tr>';
                } else {
                    transactions.forEach(txn => {
                        const typeClass = txn.type === 'added' ? 'success' : (txn.type === 'removed' ? 'danger' : 'warning');
                        const typeIcon = txn.type === 'added' ? 'plus' : (txn.type === 'removed' ? 'minus' : 'alert');
                        
                        html += `
                            <tr>
                                <td>${formatDate(txn.created_at)}</td>
                                <td><label class="badge badge-${typeClass}">${txn.type.toUpperCase()}</label></td>
                                <td>KSh ${parseFloat(txn.amount).toFixed(2)}</td>
                                <td>${txn.reason || '-'}</td>
                                <td>${txn.admin_name || 'System'}</td>
                                <td>KSh ${parseFloat(txn.new_balance).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }
                document.getElementById('history-transactions').innerHTML = html;
            }
        },
        error: function() {
            toastr.error('Failed to load user history');
        }
    });
}

// Show Statistics
function showStats() {
    document.getElementById('stats-panel').style.display = 'block';
    loadStats();
}

// Hide Statistics
function hideStats() {
    document.getElementById('stats-panel').style.display = 'none';
}

// Load Statistics
function loadStats() {
    const container = document.getElementById('stats-content');
    container.innerHTML = '<p class="text-center"><i class="mdi mdi-loading mdi-spin"></i> Loading statistics...</p>';
    
    $.ajax({
        url: '/admin/freebet/stats',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayStats(response.data);
            }
        },
        error: function() {
            container.innerHTML = '<p class="text-center text-danger">Failed to load statistics</p>';
        }
    });
}

// Display Statistics
function displayStats(data) {
    const summary = data.summary;
    const topUsers = data.top_users;
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Freebet</h6>
                        <h3>KSh ${summary.total_freebet.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Added</h6>
                        <h3 class="text-success">+KSh ${summary.total_added.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Removed</h6>
                        <h3 class="text-danger">-KSh ${summary.total_removed.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Net Change</h6>
                        <h3>${summary.net_change >= 0 ? '+' : ''}KSh ${summary.net_change.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <h5>Top Users by Freebet Balance</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Freebet Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    topUsers.forEach((user, index) => {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${user.userid}</td>
                <td>${user.username}</td>
                <td>KSh ${parseFloat(user.freebet_amount).toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewUserHistory(${user.userid})">
                        <i class="mdi mdi-history"></i> History
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    document.getElementById('stats-content').innerHTML = html;
}

// Helper Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery is loaded
    if (typeof $ === 'undefined') {
        console.error('jQuery not loaded!');
        return;
    }
    
    // Add CSRF token to all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    console.log('Freebet management JS initialized');
});
