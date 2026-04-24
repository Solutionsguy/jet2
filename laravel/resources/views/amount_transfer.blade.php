@extends('Layout.usergame2')

@section('css')
<style>
    .transfer-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .transfer-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .transfer-header h2 {
        color: #fff;
        font-weight: 900;
        letter-spacing: 1px;
    }
    
    .glass-card {
        background: rgba(255, 255, 255, 0.03) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }

    .input-field-row {
        display: flex;
        align-items: center;
        background: rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }
    .field-icon { color: #ff9500; margin-right: 12px; display: flex; }
    .field-input {
        background: transparent;
        border: none;
        color: #fff;
        font-weight: 600;
        width: 100%;
        outline: none;
    }

    .theme-btn {
        width: 100%;
        background: linear-gradient(135deg, #ff9500 0%, #ff5e00 100%);
        border: none;
        color: #000;
        font-weight: 900;
        text-transform: uppercase;
        padding: 18px;
        border-radius: 14px;
        letter-spacing: 1px;
        box-shadow: 0 8px 20px rgba(255, 94, 0, 0.3);
        transition: all 0.3s ease;
    }
    .theme-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(255, 94, 0, 0.5);
    }
</style>
@endsection

@section('content')
<div class="transfer-container py-5">
    <div class="transfer-header">
        <h2>TRANSFER FUNDS</h2>
        <p class="text-grey">Send money instantly to another JetMtaa user</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-card">
                <div class="d-flex align-items-center mb-4">
                    <span class="material-symbols-outlined text-warning me-2">send</span>
                    <h5 class="text-white mb-0">Internal Wallet Transfer</h5>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">person</span></span>
                    <input type="text" class="field-input" id="receiver_id" placeholder="Receiver ID or Username">
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">payments</span></span>
                    <input type="number" class="field-input" id="transfer_amount" placeholder="Amount to Transfer (KSh)">
                </div>

                <div class="alert alert-warning bg-dark border-secondary small mb-4">
                    <i class="material-symbols-outlined f-14 align-middle me-1">info</i>
                    Transfers are instant and cannot be reversed.
                </div>

                <button class="theme-btn" onclick="initiateTransfer()">Confirm Transfer</button>
            </div>
            
            <div id="transfer_status" class="mt-4" style="display:none;">
                <div id="transfer_loading" class="alert alert-info border-0 bg-dark text-info"><i class="mdi mdi-loading mdi-spin me-2"></i>Processing...</div>
                <div id="transfer_error" class="alert alert-danger border-0 bg-dark text-danger"><i class="mdi mdi-alert-circle me-2"></i><span id="transfer_error_msg"></span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function initiateTransfer() {
        var receiver = $('#receiver_id').val();
        var amount = $('#transfer_amount').val();
        
        if(!receiver || !amount) {
            toastr.error("Please fill all fields");
            return;
        }

        $('#transfer_status').show();
        $('#transfer_loading').show();
        $('#transfer_error').hide();

        $.ajax({
            url: '/wallet_transfer',
            type: 'POST',
            data: {
                receiver_id: receiver,
                amount: amount,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                $('#transfer_loading').hide();
                if(res.isSuccess) {
                    toastr.success(res.message);
                    setTimeout(() => { location.reload(); }, 2000);
                } else {
                    $('#transfer_error').show();
                    $('#transfer_error_msg').text(res.message);
                }
            },
            error: function() {
                $('#transfer_loading').hide();
                $('#transfer_error').show();
                $('#transfer_error_msg').text("Server error occurred");
            }
        });
    }
</script>
@endsection
