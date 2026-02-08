<!-- User Freebet History Modal -->
<div class="modal fade" id="userHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(90deg, #FF9500, #FFA500); color: white;">
                <h5 class="modal-title">
                    <i class="mdi mdi-history"></i> User Freebet History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>User:</strong> <span id="history-username">-</span></p>
                            <p class="mb-0"><strong>User ID:</strong> <span id="history-user-id">-</span></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-0"><strong>Current Freebet Balance:</strong></p>
                            <h4 class="mb-0 text-success">KSh <span id="history-current-balance">0.00</span></h4>
                        </div>
                    </div>
                </div>
                
                <h6>Transaction History</h6>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Admin</th>
                                <th>Balance After</th>
                            </tr>
                        </thead>
                        <tbody id="history-transactions">
                            <tr>
                                <td colspan="6" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
