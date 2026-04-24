@extends('Layout.usergame2')

@section('css')
<style>
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .profile-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .profile-header h2 {
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

    .profile-avatar-section {
        text-align: center;
        margin-bottom: 30px;
    }
    .profile-avatar-section img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid #ff9500;
        padding: 5px;
        background: rgba(0,0,0,0.3);
        margin-bottom: 15px;
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
    .field-input:disabled { opacity: 0.5; color: #888; }

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
</style>
@endsection

@section('content')
<div class="profile-container py-5">
    <div class="profile-header">
        <h2>PERSONAL DETAILS</h2>
        <p class="text-grey">Manage your account information and preferences</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-card">
                <div class="profile-avatar-section">
                    <img src="{{ user('image') ?? asset('images/user-default.png') }}" alt="Avatar">
                    <h5 class="text-white mb-0">{{ user('name') }}</h5>
                    <p class="text-warning small fw-700">Verified Member</p>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">person</span></span>
                    <input type="text" class="field-input" value="{{ user('name') }}" disabled>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">mail</span></span>
                    <input type="text" class="field-input" value="{{ user('email') }}" disabled>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">call</span></span>
                    <input type="text" class="field-input" value="{{ user('mobile') }}" disabled>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">payments</span></span>
                    <input type="text" class="field-input" value="Default Currency: {{ user('currency') }}" disabled>
                </div>

                <div class="alert alert-info bg-dark border-secondary small mb-4">
                    <i class="material-symbols-outlined f-14 align-middle me-1">lock</i>
                    To change your personal details, please contact support.
                </div>

                <a href="/logout" class="theme-btn text-center text-decoration-none d-block" style="background: rgba(255,255,255,0.05); color: #fff; box-shadow: none;">Sign Out</a>
            </div>
        </div>
    </div>
</div>
@endsection
