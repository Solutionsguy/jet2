 <!-- partial:partials/_sidebar.html -->
 <nav class="sidebar sidebar-offcanvas" id="sidebar">
     <ul class="nav">
         <li class="nav-item nav-profile">
             <a href="#" class="nav-link">
                 <div class="nav-profile-image">
                     <img src="/aviatoradmin/assets/images/faces/face1.jpg" alt="profile">
                     <span class="login-status online"></span>
                     <!--change to offline or busy as needed-->
                 </div>
                 <div class="nav-profile-text d-flex flex-column">
                     <span class="font-weight-bold mb-2">{{admin('name')}}</span>
                     <span class="text-secondary text-small">Administration</span>
                 </div>
                 <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
             </a>
         </li>
         
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/dashboard') }}">
                 <span class="menu-title">Dashboard</span>
                 <i class="mdi mdi-home menu-icon"></i>
             </a>
         </li>

         @if(has_permission('view_users'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/user-list') }}">
                 <span class="menu-title">User Management</span>
                 <i class="mdi mdi-account-network menu-icon"></i>
             </a>
         </li>
         @endif

         @if(has_permission('manage_deposits'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/recharge-history') }}">
                 <span class="menu-title">Recharge history</span>
                 <i class="mdi mdi-account-network menu-icon"></i>
             </a>
         </li>
         @endif

         @if(has_permission('manage_withdrawals'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/withdrawal-history') }}">
                 <span class="menu-title">Withdrawal history</span>
                 <i class="mdi mdi-account-network menu-icon"></i>
             </a>
         </li>
         @endif

         @if(has_permission('game_settings'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/amount-setup') }}">
                 <span class="menu-title">Amount Setup</span>
                 <i class="mdi mdi-account-network menu-icon"></i>
             </a>
         </li>
         @endif

         @if(has_permission('game_settings'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/bank-detail') }}">
                 <span class="menu-title">Bank Detail</span>
                 <i class="mdi mdi-account-network menu-icon"></i>
             </a>
         </li>
         @endif

         @if(has_permission('full_access'))
         <li class="nav-item">
             <a class="nav-link" data-bs-toggle="collapse" href="#admin-management" aria-expanded="false"
                 aria-controls="admin-management">
                 <span class="menu-title">Admin Settings</span>
                 <i class="menu-arrow"></i>
                 <i class="mdi mdi-shield-account menu-icon" style="color: #ff3296;"></i>
             </a>
             <div class="collapse" id="admin-management">
                 <ul class="nav flex-column sub-menu">
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ url('admin/roles') }}">Manage Roles</a>
                     </li>
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ url('admin/sub-admins') }}">Manage Admins</a>
                     </li>
                 </ul>
             </div>
         </li>
         @endif

         @if(has_permission('manage_rain'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/rain') }}">
                 <span class="menu-title">Rain Management</span>
                 <i class="mdi mdi-cloud-download menu-icon" style="color: #FF9500;"></i>
             </a>
         </li>
         @endif

         @if(has_permission('manage_freebets'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/freebet') }}">
                 <span class="menu-title">Freebet Management</span>
                 <i class="mdi mdi-gift menu-icon" style="color: #667eea;"></i>
             </a>
         </li>
         @endif

         @if(has_permission('manage_chat'))
         <li class="nav-item">
             <a class="nav-link" href="{{ url('admin/chat-management') }}">
                 <span class="menu-title">Chat Management</span>
                 <i class="mdi mdi-message-text menu-icon" style="color: #ff3296;"></i>
             </a>
         </li>
         @endif

         @if(has_permission('game_settings'))
         <li class="nav-item">
             <a class="nav-link" data-bs-toggle="collapse" href="#game-management" aria-expanded="false"
                 aria-controls="game-management">
                 <span class="menu-title">Manage Games</span>
                 <i class="menu-arrow"></i>
                 <i class="mdi mdi-gamepad-variant menu-icon" style="color: #8e44ad;"></i>
             </a>
             <div class="collapse" id="game-management">
                 <ul class="nav flex-column sub-menu">
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ route('admin.game.index') }}">All Games</a>
                     </li>
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ route('admin.category.index') }}">Game Categories</a>
                     </li>
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ route('admin.game.log') }}">Game Logs</a>
                     </li>
                 </ul>
             </div>
         </li>
         @endif

         @if(has_permission('manage_p2p'))
         <li class="nav-item">
             <a class="nav-link" data-bs-toggle="collapse" href="#p2p-management" aria-expanded="false"
                 aria-controls="p2p-management">
                 <span class="menu-title">P2P Management</span>
                 <i class="menu-arrow"></i>
                 <i class="mdi mdi-account-switch menu-icon" style="color: #00d25b;"></i>
             </a>
             <div class="collapse" id="p2p-management">
                 <ul class="nav flex-column sub-menu">
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ route('admin.p2p.peers') }}">Peer Numbers</a>
                     </li>
                     <li class="nav-item"> 
                        <a class="nav-link" href="{{ route('admin.p2p.withdrawals') }}">P2P Withdrawals</a>
                     </li>
                 </ul>
             </div>
         </li>
         @endif
     </ul>
 </nav>
