/**
 * Admin Rain Management JavaScript
 */

// CSRF Token helper
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

// Helper for notification fallback
const showNotification = (type, message) => {
    // Try iziToast first (common in this admin panel)
    if (typeof iziToast !== 'undefined') {
        iziToast[type === 'error' ? 'error' : 'success']({
            title: type.toUpperCase(),
            message: message,
            position: 'topRight'
        });
    } 
    // Fallback to toastr
    else if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } 
    // Final fallback to alert
    else {
        console.log(type.toUpperCase() + ": " + message);
        alert(type.toUpperCase() + ": " + message);
    }
};

// Create Support Rain
function createSupportRain() {
    const amountField = document.getElementById('rain-amount');
    const winnersField = document.getElementById('rain-winners');
    const messageField = document.getElementById('rain-message');

    if (!amountField || !winnersField) {
        showNotification('error', 'Required form fields missing');
        return;
    }

    const amount = amountField.value;
    const winners = winnersField.value;
    const message = messageField ? messageField.value : '';
    
    if (!amount || !winners) {
        showNotification('error', 'Please fill in all required fields');
        return;
    }
    
    if (parseFloat(amount) < 10) {
        showNotification('error', 'Amount must be at least KSh 10');
        return;
    }
    
    if (parseInt(winners) < 2 || parseInt(winners) > 100) {
        showNotification('error', 'Winners must be between 2 and 100');
        return;
    }
    
    const btn = document.getElementById('create-rain-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    }
    
    $.ajax({
        url: '/admin/rain/create',
        type: 'POST',
        data: {
            _token: getCsrfToken(),
            amount_per_user: amount,
            num_winners: winners,
            message: message
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                $('#createRainModal').modal('hide');
                const form = document.getElementById('createRainForm');
                if (form) form.reset();
                
                // Reload page to show new rain
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('error', response.message || 'Failed to create rain');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Server error';
            showNotification('error', error);
        },
        complete: function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-cloud-rain"></i> Create Rain';
            }
        }
    });
}

// Auto-Rain Settings
function saveAutoRainSettings() {
    const enabledInput = document.getElementById('auto-rain-enabled');
    const form = document.getElementById('auto-rain-settings-form');
    
    if (!enabledInput || !form) {
        showNotification('error', 'Settings form not found');
        return;
    }

    const enabled = enabledInput.checked ? '1' : '0';
    const formData = new FormData(form);
    
    const data = {
        _token: getCsrfToken(),
        enabled: enabled,
        amount: formData.get('amount'),
        winners: formData.get('winners'),
        interval: formData.get('interval')
    };

    $.ajax({
        url: '/admin/rain/auto-settings',
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                setTimeout(() => { window.location.reload(); }, 1000);
            }
        },
        error: function(xhr) {
            showNotification('error', xhr.responseJSON?.message || 'Failed to update settings');
        }
    });
}

function triggerAutoRainNow() {
    if (!confirm('Drop an automated rain immediately?')) return;

    $.ajax({
        url: '/admin/rain/auto-trigger',
        type: 'POST',
        data: { _token: getCsrfToken() },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                setTimeout(() => { window.location.reload(); }, 1000);
            }
        },
        error: function(xhr) {
            showNotification('error', xhr.responseJSON?.message || 'Failed to trigger rain');
        }
    });
}

// View Rain Participants
function viewParticipants(rainId) {
    $('#participantsModal').modal('show');
    
    $.ajax({
        url: '/admin/rain/' + rainId + '/participants',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const rain = response.data.rain;
                const participants = response.data.participants;
                
                // Update rain info
                const idEl = document.getElementById('p-rain-id');
                const amtEl = document.getElementById('p-amount');
                const slotsEl = document.getElementById('p-slots');
                const statusEl = document.getElementById('p-status');

                if (idEl) idEl.textContent = rain.id;
                if (amtEl) amtEl.textContent = 'KSh ' + parseFloat(rain.amount_per_user).toFixed(2);
                if (slotsEl) slotsEl.textContent = rain.num_winners;
                if (statusEl) statusEl.innerHTML = getStatusBadge(rain.status);
                
                // Update participants list
                let html = '';
                if (participants.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-muted">No participants yet</td></tr>';
                } else {
                    participants.forEach((p, index) => {
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${p.username}</td>
                                <td>${p.is_winner ? '<span class="badge bg-success">Winner</span>' : '<span class="badge bg-secondary">Participant</span>'}</td>
                                <td>KSh ${parseFloat(p.amount_won).toFixed(2)}</td>
                                <td>${formatDate(p.created_at)}</td>
                            </tr>
                        `;
                    });
                }
                const listEl = document.getElementById('participants-list');
                if (listEl) listEl.innerHTML = html;
            }
        },
        error: function() {
            showNotification('error', 'Failed to load participants');
        }
    });
}

// Cancel Rain
function cancelRain(rainId) {
    if (!confirm('⚠️ Are you sure you want to DELETE this rain?\n\n• User-created rains will be REFUNDED\n• Support rains will be CANCELLED\n• This action cannot be undone!')) {
        return;
    }
    
    $.ajax({
        url: '/admin/rain/' + rainId + '/cancel',
        type: 'POST',
        data: {
            _token: getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('error', response.message || 'Failed to cancel rain');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Server error';
            showNotification('error', error);
        }
    });
}

// Load Rain History
function loadRainHistory() {
    const statusEl = document.getElementById('filter-status');
    const typeEl = document.getElementById('filter-type');
    const fromEl = document.getElementById('filter-date-from');
    const toEl = document.getElementById('filter-date-to');
    
    const container = document.getElementById('rain-history-container');
    if (!container) return;
    
    container.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    
    $.ajax({
        url: '/admin/rain/history',
        type: 'GET',
        data: {
            status: statusEl ? statusEl.value : 'all',
            type: typeEl ? typeEl.value : 'all',
            date_from: fromEl ? fromEl.value : '',
            date_to: toEl ? toEl.value : ''
        },
        success: function(response) {
            if (response.success) {
                displayRainHistory(response.data);
            }
        },
        error: function() {
            container.innerHTML = '<p class="text-center text-danger">Failed to load history</p>';
        }
    });
}

// Display Rain History
function displayRainHistory(data) {
    const container = document.getElementById('rain-history-container');
    if (!container) return;
    
    if (data.data.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No rains found</p>';
        return;
    }
    
    let html = `
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Creator</th>
                    <th>Type</th>
                    <th>Amount/User</th>
                    <th>Slots</th>
                    <th>Total</th>
                    <th>Participants</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    data.data.forEach(rain => {
        html += `
            <tr>
                <td>${rain.id}</td>
                <td>${rain.creator_name}</td>
                <td>${rain.creator?.isadmin ? '<span class="badge bg-warning">Support</span>' : '<span class="badge bg-info">User</span>'}</td>
                <td>KSh ${parseFloat(rain.amount_per_user).toFixed(2)}</td>
                <td>${rain.num_winners}</td>
                <td>KSh ${parseFloat(rain.total_amount).toFixed(2)}</td>
                <td>${rain.participants_count}/${rain.num_winners}</td>
                <td>${getStatusBadge(rain.status)}</td>
                <td>${formatDate(rain.created_at)}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    container.innerHTML = html;
}

// Load Analytics
function loadAnalytics() {
    const container = document.getElementById('analytics-container');
    if (!container) return;
    
    $.ajax({
        url: '/admin/rain/analytics',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayAnalytics(response.data);
            }
        },
        error: function() {
            container.innerHTML = '<p class="text-center text-danger">Failed to load analytics</p>';
        }
    });
}

// Display Analytics
function displayAnalytics(data) {
    const summary = data.summary;
    const activeUsers = data.most_active_users;
    const container = document.getElementById('analytics-container');
    if (!container) return;
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Rains</h6>
                        <h3>${summary.total_rains}</h3>
                        <small>Admin: ${summary.admin_rains} | User: ${summary.user_rains}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Distributed</h6>
                        <h3>KSh ${summary.total_distributed.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Participants</h6>
                        <h3>${summary.total_participants}</h3>
                        <small>Avg: ${summary.total_rains > 0 ? (summary.total_participants / summary.total_rains).toFixed(1) : 0}/rain</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Support Distributed</h6>
                        <h3>KSh ${summary.admin_distributed.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <h5>Most Active Rain Users (Top 10)</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Participations</th>
                    <th>Total Won</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    activeUsers.forEach((user, index) => {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${user.username}</td>
                <td>${user.participation_count}</td>
                <td>KSh ${parseFloat(user.total_won).toFixed(2)}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    container.innerHTML = html;
}

// Helper Functions
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-primary">Active</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Calculate active rains total
function updateActiveStats() {
    let totalAmount = 0;
    const table = document.getElementById('active-rains-table');
    if (!table) return;

    table.querySelectorAll('tbody tr').forEach(row => {
        const amountText = row.cells[4]?.textContent;
        if (amountText) {
            const amount = parseFloat(amountText.replace('KSh ', '').replace(',', ''));
            if (!isNaN(amount)) {
                totalAmount += amount;
            }
        }
    });
    
    const totalEl = document.getElementById('total-active-amount');
    if (totalEl) {
        totalEl.textContent = 'KSh ' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
    }
}

// Load analytics when tab is clicked
document.querySelector('a[href="#analytics"]')?.addEventListener('shown.bs.tab', function() {
    loadAnalytics();
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateActiveStats();
});
