<!-- Add Freebet Modal -->
<div class="modal fade" id="addFreebetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(90deg, #667eea, #764ba2); color: white;">
                <h5 class="modal-title">
                    <i class="mdi mdi-plus-circle"></i> Add Freebet to User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addFreebetForm">
                    <div class="mb-3">
                        <label class="form-label">User ID, Username, or Name *</label>
                        <input type="text" class="form-control" id="add-user-identifier" required 
                               placeholder="e.g., 123 or john_doe">
                        <small class="text-muted">Enter user ID, username, or name to search</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Freebet Amount (KSh) *</label>
                        <input type="number" class="form-control" id="add-amount" required min="1" step="0.01">
                        <small class="text-muted">Minimum: KSh 1</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" id="add-reason" 
                               placeholder="e.g., Welcome bonus, Compensation">
                        <small class="text-muted">Optional - for record keeping</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i> Freebet will be added to user's freebet wallet instantly
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-gradient-info" onclick="addFreebet()" id="add-freebet-btn">
                    <i class="mdi mdi-plus-circle"></i> Add Freebet
                </button>
            </div>
        </div>
    </div>
</div>
