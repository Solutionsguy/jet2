<!-- Rain Participants Modal -->
<div class="modal fade" id="participantsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-users"></i> Rain Participants
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="participants-info" class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Rain ID:</strong> <span id="p-rain-id">-</span></p>
                            <p><strong>Amount/User:</strong> <span id="p-amount">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Slots:</strong> <span id="p-slots">-</span></p>
                            <p><strong>Status:</strong> <span id="p-status">-</span></p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Amount Won</th>
                                <th>Claimed At</th>
                            </tr>
                        </thead>
                        <tbody id="participants-list">
                            <tr>
                                <td colspan="5" class="text-center">Loading...</td>
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
