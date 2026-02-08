<!-- Bulk Freebet Modal -->
<div class="modal fade" id="bulkFreebetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(90deg, #4facfe, #00f2fe); color: white;">
                <h5 class="modal-title">
                    <i class="mdi mdi-account-multiple-plus"></i> Bulk Freebet Distribution
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkFreebetForm">
                    <div class="mb-3">
                        <label class="form-label">Distribution Method</label>
                        <select class="form-control" id="bulk-method" onchange="toggleBulkMethod()">
                            <option value="manual">Select Users Manually</option>
                            <option value="all">All Users</option>
                            <option value="active">Active Users (Last 7 Days)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="manual-users-section">
                        <label class="form-label">User IDs (comma separated) *</label>
                        <textarea class="form-control" id="bulk-user-ids" rows="3" 
                                  placeholder="e.g., 1, 5, 12, 25, 48"></textarea>
                        <small class="text-muted">Enter user IDs separated by commas</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount per User (KSh) *</label>
                        <input type="number" class="form-control" id="bulk-amount" required min="1" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" id="bulk-reason" 
                               placeholder="e.g., Promotional bonus, Weekend special">
                    </div>
                    
                    <div class="alert alert-info">
                        <div id="bulk-preview">
                            <strong>Preview:</strong><br>
                            Users: <span id="bulk-user-count">0</span><br>
                            Amount per user: KSh <span id="bulk-per-user">0.00</span><br>
                            <strong>Total cost: KSh <span id="bulk-total">0.00</span></strong>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-gradient-success" onclick="bulkAddFreebet()" id="bulk-freebet-btn">
                    <i class="mdi mdi-account-multiple-plus"></i> Distribute
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleBulkMethod() {
    const method = document.getElementById('bulk-method').value;
    const manualSection = document.getElementById('manual-users-section');
    
    if (method === 'manual') {
        manualSection.style.display = 'block';
    } else {
        manualSection.style.display = 'none';
    }
}

// Update bulk preview
document.getElementById('bulk-user-ids')?.addEventListener('input', updateBulkPreview);
document.getElementById('bulk-amount')?.addEventListener('input', updateBulkPreview);
document.getElementById('bulk-method')?.addEventListener('change', updateBulkPreview);

function updateBulkPreview() {
    const method = document.getElementById('bulk-method').value;
    const amount = parseFloat(document.getElementById('bulk-amount').value) || 0;
    let userCount = 0;
    
    if (method === 'manual') {
        const userIds = document.getElementById('bulk-user-ids').value.split(',').filter(id => id.trim());
        userCount = userIds.length;
    } else if (method === 'all') {
        userCount = '?';
    } else if (method === 'active') {
        userCount = '?';
    }
    
    const total = userCount === '?' ? '?' : (userCount * amount).toFixed(2);
    
    document.getElementById('bulk-user-count').textContent = userCount;
    document.getElementById('bulk-per-user').textContent = amount.toFixed(2);
    document.getElementById('bulk-total').textContent = total === '?' ? 'Calculating...' : ('KSh ' + total);
}
</script>
