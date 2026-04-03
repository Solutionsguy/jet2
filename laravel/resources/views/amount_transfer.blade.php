@extends('Layout.usergame2')

@section('css')
<style>
    .transfer-container {
        max-width: 500px;
        margin: 100px auto;
        padding: 20px;
    }
    .transfer-card {
        background: rgba(25, 26, 27, 0.95);
        border: 1px solid #2a2b2e;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .balance-badge {
        background: rgba(255, 149, 0, 0.1);
        border: 1px dashed #ff9500;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        margin-bottom: 25px;
    }
    .balance-badge .label {
        color: #888;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
        margin-bottom: 5px;
    }
    .balance-badge .amount {
        color: #ff9500;
        font-size: 24px;
        font-weight: 800;
    }
    .input-group-custom {
        background: #111;
        border: 1px solid #333;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: border-color 0.2s;
    }
    .input-group-custom:focus-within {
        border-color: #ff9500;
    }
    .input-group-custom .input-group-text {
        background: transparent;
        border: none;
        color: #ff9500;
    }
    .input-group-custom .form-control {
        background: transparent;
        border: none;
        color: #fff;
        padding: 12px 10px;
    }
    .input-group-custom .form-control:focus {
        box-shadow: none;
    }
    .transfer-btn {
        width: 100%;
        background: #ff9500;
        color: #000;
        border: none;
        padding: 12px;
        border-radius: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.2s;
        margin-top: 10px;
    }
    .transfer-btn:hover {
        background: #fa5e00;
        transform: translateY(-2px);
    }
    .transfer-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .transfer-header h2 {
        color: #ff9500;
        font-weight: 800;
        text-transform: uppercase;
    }
    .transfer-header p {
        color: #888;
        font-size: 14px;
    }
    .security-note {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.03);
        padding: 10px;
        border-radius: 8px;
        margin-top: 20px;
        color: #666;
        font-size: 11px;
    }
    @media (max-width: 768px) {
        .transfer-container {
            margin-top: 80px;
            padding: 15px;
        }
        .transfer-card {
            padding: 20px;
        }
    }
</style>
@endsection

@section('content')
<div class="transfer-container">
    <div class="transfer-header">
        <h2>{{$title}}</h2>
        <p>Instantly send funds to another player's wallet</p>
    </div>

    <div class="transfer-card">
        <!-- Optional: Show current balance if needed -->
        <div class="balance-badge">
            <span class="label">Available Funds</span>
            <span class="amount">KSh {{ wallet(user('id')) }}</span>
        </div>

        <form action="/wallet_transfer" method="post" id="amounttransfer">
            @csrf
            <div class="input-group input-group-custom">
                <span class="input-group-text">
                    <span class="material-symbols-outlined">person</span>
                </span>
                <input type="text" class="form-control" id="userid" placeholder="Recipient User ID" name="userid" required>
            </div>

            <div class="input-group input-group-custom">
                <span class="input-group-text">
                    <span class="material-symbols-outlined">payments</span>
                </span>
                <input type="number" class="form-control" id="amount" placeholder="Amount (KSh)" name="amount" required min="1">
            </div>

            <div id="promo_code_error" class="error mb-3" style="font-size: 13px;"></div>

            <button type="submit" class="transfer-btn">Confirm Transfer</button>
        </form>

        <div class="security-note">
            <span class="material-symbols-outlined me-2" style="font-size: 18px;">verified_user</span>
            <span>Secure Peer-to-Peer Transfer. Please double-check the recipient ID before confirming.</span>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    // Simplified cron if needed, though usually not required on this page
    /*
    setInterval(() => {
        $.ajax({
            url: '/game-cron',
            type: "GET",
            dataType: "json",
            success: function(intialData) {}
        });
    }, 5000); 
    */
    
    // Simple validation feedback
    $('#amounttransfer').on('submit', function(e) {
        const amount = parseFloat($('#amount').val());
        const balance = parseFloat('{{ wallet(user('id')) }}'.replace(/,/g, ''));
        
        if (amount > balance) {
            e.preventDefault();
            $('#promo_code_error').text('Insufficient balance for this transfer.').show();
            return false;
        }
        
        return confirm('Are you sure you want to transfer KSh ' + amount + ' to User ID ' + $('#userid').val() + '?');
    });
</script>
@endsection