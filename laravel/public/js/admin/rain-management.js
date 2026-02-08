/**
 * Admin Rain Management JavaScript
 */

// Create Support Rain
function createSupportRain() {
    const amount = document.getElementById('rain-amount').value;
    const winners = document.getElementById('rain-winners').value;
    const message = document.getElementById('rain-message').value;
    
    if (!amount || !winners) {
        toastr.error('Please fill in all required fields');
        return;
    }
    
    if (parseFloat(amount) < 10) {
        toastr.error('Amount must be at least KSh 10');
        return;
    }
    
    if (parseInt(winners) < 2 || parseInt(winners) > 100) {
        toastr.error('Winners must be between 2 and 100');
        return;
    }
    
    const btn = document.getElementById('create-rain-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    $.ajax({
        url: '/admin/rain/create',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            amount_per_user: amount,
            num_winners: winners,
            message: message
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#createRainModal').modal('hide');
                document.getElementById('createRainForm').reset();
                calculateRainTotal();
                
                // Reload page to show new rain
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                toastr.error(response.message || 'Failed to create rain');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Server error';
            toastr.error(error);
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cloud-rain"></i> Create Rain';
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
                document.getElementById('p-rain-id').textContent = rain.id;
                document.getElementById('p-amount').textContent = 'KSh ' + parseFloat(rain.amount_per_user).toFixed(2);
                document.getElementById('p-slots').textContent = rain.num_winners;
                document.getElementById('p-status').innerHTML = getStatusBadge(rain.status);
                
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
                document.getElementById('participants-list').innerHTML = html;
            }
        },
        error: function() {
            toastr.error('Failed to load participants');
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
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                toastr.error(response.message || 'Failed to cancel rain');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Server error';
            toastr.error(error);
        }
    });
}

// Load Rain History
function loadRainHistory() {
    const status = document.getElementById('filter-status').value;
    const type = document.getElementById('filter-type').value;
    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo = document.getElementById('filter-date-to').value;
    
    const container = document.getElementById('rain-history-container');
    container.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    
    $.ajax({
        url: '/admin/rain/history',
        type: 'GET',
        data: {
            status: status,
            type: type,
            date_from: dateFrom,
            date_to: dateTo
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
    
    // Add pagination if available
    if (data.links) {
        html += '<div class="d-flex justify-content-center mt-3">';
        // Add pagination links here
        html += '</div>';
    }
    
    container.innerHTML = html;
}

// Load Analytics
function loadAnalytics() {
    const container = document.getElementById('analytics-container');
    
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
                        <small>Avg: ${summary.avg_participants}/rain</small>
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
    
    document.getElementById('analytics-container').innerHTML = html;
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
    document.querySelectorAll('#active-rains-table tbody tr').forEach(row => {
        const amountText = row.cells[4]?.textContent;
        if (amountText) {
            const amount = parseFloat(amountText.replace('KSh ', '').replace(',', ''));
            if (!isNaN(amount)) {
                totalAmount += amount;
            }
        }
    });
    
    document.getElementById('total-active-amount').textContent = 
        'KSh ' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
}

// Load analytics when tab is clicked
document.querySelector('a[href="#analytics"]')?.addEventListener('shown.bs.tab', function() {
    loadAnalytics();
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateActiveStats();
});
