<!-- Remove Freebet Modal -->
<div class="modal fade" id="removeFreebetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-minus-circle"></i> Remove Freebet from User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="removeFreebetForm">
                    <div class="mb-3">
                        <label class="form-label">User ID, Username, or Name *</label>
                        <input type="text" class="form-control" id="remove-user-identifier" required 
                               placeholder="e.g., 123 or john_doe">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount to Remove (KSh) *</label>
                        <input type="number" class="form-control" id="remove-amount" required min="1" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <input type="text" class="form-control" id="remove-reason" required
                               placeholder="e.g., Violation, Adjustment">
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert"></i> <strong>Warning:</strong> This will deduct from user's freebet balance. Make sure they have sufficient balance.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="removeFreebet()" id="remove-freebet-btn">
                    <i class="mdi mdi-minus-circle"></i> Remove Freebet
                </button>
            </div>
        </div>
    </div>
</div>
