@extends('Layout.usergame2')

@section('css')
<style>
    .profile-container {
        max-width: 1000px;
        margin: 100px auto;
        padding: 20px;
    }
    .profile-header-card {
        background: linear-gradient(135deg, #1a1b1d 0%, #101011 100%);
        border: 1px solid #2a2b2e;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .profile-avatar-large {
        width: 100px;
        height: 100px;
        background: #222;
        border: 3px solid #ff9500;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .profile-header-info h2 {
        color: #fff;
        margin: 0;
        font-weight: 800;
        text-transform: uppercase;
    }
    .profile-header-info p {
        color: #ff9500;
        margin: 0;
        font-weight: 600;
        font-size: 14px;
    }
    .info-card {
        background: rgba(25, 26, 27, 0.95);
        border: 1px solid #2a2b2e;
        border-radius: 15px;
        padding: 20px;
        height: 100%;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .info-card-title {
        color: #ff9500;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #1a1b1d;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        color: #666;
        font-size: 13px;
    }
    .info-value {
        color: #fff;
        font-weight: 600;
        font-size: 13px;
        text-align: right;
    }
    .lock-icon {
        color: #444;
        font-size: 14px !important;
        margin-left: 8px;
    }
    @media (max-width: 768px) {
        .profile-container {
            margin-top: 80px;
            padding: 15px;
        }
        .profile-header-card {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
        }
    }
</style>
@endsection

@section('content')
<div class="profile-container">
    <!-- Header Card -->
    <div class="profile-header-card">
        <div class="profile-avatar-large">
            <img src="{{asset('images/avtar/av-1.png')}}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="profile-header-info">
            <p>Welcome back,</p>
            <h2>{{ user('name') }}</h2>
            <div class="mt-2">
                <span class="badge bg-dark border border-secondary text-grey">User ID: {{ user('id') }}</span>
                <span class="badge bg-dark border border-warning text-warning ms-1">Active Member</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Account Details Card -->
        <div class="col-md-6">
            <div class="info-card">
                <div class="info-card-title">
                    <span class="material-symbols-outlined">account_balance</span>
                    Bank Details
                </div>
                <div class="info-row">
                    <span class="info-label">Bank Name</span>
                    <span class="info-value">
                        {{isset($bank->bankname)?$bank->bankname:'N/A'}}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Number</span>
                    <span class="info-value">
                        {{isset($bank->accountno)?$bank->accountno:'N/A'}}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">IFSC Code</span>
                    <span class="info-value">
                        {{isset($bank->ifsccode)?$bank->ifsccode:'N/A'}}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Branch</span>
                    <span class="info-value">
                        {{isset($bank->branchname)?$bank->branchname:'N/A'}}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Contact Details Card -->
        <div class="col-md-6">
            <div class="info-card">
                <div class="info-card-title">
                    <span class="material-symbols-outlined">contact_phone</span>
                    Contact Info
                </div>
                <div class="info-row">
                    <span class="info-label">Mobile Number</span>
                    <span class="info-value">
                        {{user('mobile') ? user('mobile') : 'Not Linked' }}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email Address</span>
                    <span class="info-value">
                        {{ user('email') }}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Country</span>
                    <span class="info-value">
                        {{ ucfirst(user('country')) }}
                        <span class="material-symbols-outlined lock-icon">lock</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Personal Details Card -->
        <div class="col-12">
            <div class="info-card">
                <div class="info-card-title">
                    <span class="material-symbols-outlined">badge</span>
                    Personal Details
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Full Name</span>
                            <span class="info-value">{{ user('name') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gender</span>
                            <span class="info-value">{{ ucfirst(user('gender')) }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Preferred Currency</span>
                            <span class="info-value text-warning fw-bold">{{ user('currency') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Security Status</span>
                            <span class="info-value"><span class="text-success">Verified</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Note -->
    <div class="mt-4 p-3 bg-dark border border-secondary rounded-3 text-center">
        <p class="text-grey small mb-0">
            <span class="material-symbols-outlined align-middle me-1" style="font-size: 16px;">verified_user</span>
            Information is encrypted and locked. To update your details, please contact support.
        </p>
    </div>
</div>
@endsection

