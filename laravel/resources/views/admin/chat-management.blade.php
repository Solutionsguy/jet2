@extends('Layout.admindashboard')

@section('content')
<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h3 class="page-title"> Chat Management </h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chat Management</li>
            </ol>
        </nav>
    </div>

    <!-- Auto-Approve Settings -->
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Chat Settings</h4>
                    <p class="card-description"> Configure how chat messages are handled </p>
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input" type="checkbox" id="auto-approve-toggle" {{ $autoApprove ? 'checked' : '' }} onchange="toggleAutoApprove()">
                            <label class="form-check-label" for="auto-approve-toggle">Auto-Approve New Messages</label>
                        </div>
                        <small class="text-muted">If disabled, all new messages must be manually approved by an admin before they appear to users.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Messages List -->
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Chat Messages</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($messages as $message)
                                <tr id="msg-row-{{ $message->id }}" class="{{ $message->is_deleted ? 'table-danger' : ($message->is_approved ? '' : 'table-warning') }}">
                                    <td>{{ $message->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($message->avatar)
                                                <img src="{{ $message->avatar }}" alt="avatar" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                                            @else
                                                <div style="width: 30px; height: 30px; border-radius: 50%; background: #ccc; margin-right: 10px;"></div>
                                            @endif
                                            <div>
                                                <strong>{{ $message->username }}</strong>
                                                <br>
                                                <small class="text-muted">UID: {{ $message->user_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="max-width: 300px; white-space: normal;">
                                        <span id="msg-text-{{ $message->id }}">{{ $message->message }}</span>
                                    </td>
                                    <td>
                                        @if($message->is_deleted)
                                            <span class="badge badge-danger">Deleted</span>
                                        @elseif($message->is_approved)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if(!$message->is_approved && !$message->is_deleted)
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="approveMessage({{ $message->id }})" title="Approve">
                                                    <i class="mdi mdi-check"></i>
                                                </button>
                                            @endif
                                            
                                            @if($message->is_approved && !$message->is_deleted)
                                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="disapproveMessage({{ $message->id }})" title="Disapprove">
                                                    <i class="mdi mdi-close"></i>
                                                </button>
                                            @endif

                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="editMessage({{ $message->id }})" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>

                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteMessage({{ $message->id }})" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No messages found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Message Modal -->
<div class="modal fade" id="editMessageModal" tabindex="-1" role="dialog" aria-labelledby="editMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMessageModalLabel">Edit Chat Message</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editMessageForm">
                    <input type="hidden" id="edit-msg-id">
                    <div class="form-group">
                        <label for="edit-msg-content">Message</label>
                        <textarea class="form-control" id="edit-msg-content" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveMessageEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    function toggleAutoApprove() {
        const enabled = document.getElementById('auto-approve-toggle').checked;
        
        fetch('/manage_jet_secure/chat-management/auto-approve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ enabled: enabled })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast or message
                console.log('Setting updated');
            } else {
                alert('Failed to update setting: ' + data.message);
            }
        });
    }

    function approveMessage(id) {
        fetch(`/manage_jet_secure/chat-management/approve/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function disapproveMessage(id) {
        fetch(`/manage_jet_secure/chat-management/disapprove/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function editMessage(id) {
        const content = document.getElementById(`msg-text-${id}`).innerText;
        document.getElementById('edit-msg-id').value = id;
        document.getElementById('edit-msg-content').value = content;
        var myModal = new bootstrap.Modal(document.getElementById('editMessageModal'));
        myModal.show();
    }

    function saveMessageEdit() {
        const id = document.getElementById('edit-msg-id').value;
        const message = document.getElementById('edit-msg-content').value;
        
        fetch(`/manage_jet_secure/chat-management/update/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function deleteMessage(id) {
        if (!confirm('Are you sure you want to delete this message?')) return;
        
        fetch(`/manage_jet_secure/chat-management/delete/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
</script>
@endsection
