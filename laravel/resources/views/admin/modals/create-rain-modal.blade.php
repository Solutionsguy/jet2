<!-- Create Support Rain Modal -->
<div class="modal fade" id="createRainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(90deg, #FF9500, #FFA500); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-cloud-rain"></i> Create Support Rain
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createRainForm">
                    <div class="mb-3">
                        <label class="form-label">Amount Per User (KSh) *</label>
                        <input type="number" class="form-control" id="rain-amount" required min="10" step="0.01">
                        <small class="text-muted">Minimum: KSh 10</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Number of Winners *</label>
                        <input type="number" class="form-control" id="rain-winners" required min="2" max="100">
                        <small class="text-muted">Min: 2, Max: 100</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="rain-message" rows="2" maxlength="500"></textarea>
                        <small class="text-muted">Will be displayed in chat (max 500 characters)</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total Cost:</strong>
                            <h4 class="mb-0" id="rain-total">KSh 0.00</h4>
                        </div>
                        <small class="text-muted">Winners will receive freebet credits</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="createSupportRain()" id="create-rain-btn">
                    <i class="fas fa-cloud-rain"></i> Create Rain
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate rain total in real-time
document.getElementById('rain-amount')?.addEventListener('input', calculateRainTotal);
document.getElementById('rain-winners')?.addEventListener('input', calculateRainTotal);

function calculateRainTotal() {
    const amount = parseFloat(document.getElementById('rain-amount').value) || 0;
    const winners = parseInt(document.getElementById('rain-winners').value) || 0;
    const total = amount * winners;
    document.getElementById('rain-total').textContent = 'KSh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
