@extends('Layout.admindashboard')
@section('css')
    
@endsection

@section('content')
<div class="content-wrapper">
  <div class="page-header">
    <h3 class="page-title">
      <span class="page-title-icon bg-gradient-primary text-white me-2">
        <i class="mdi mdi-home"></i>
      </span> Dashboard Overview (Today)
    </h3>
  </div>

  <div class="row">
    <!-- New Users Today -->
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-danger card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">New Users <i class="mdi mdi-account-multiple-plus mdi-24px float-right"></i></h4>
          <h2 class="mb-5">{{ $stats['new_users_today'] }}</h2>
          <p class="card-text">Joined today</p>
        </div>
      </div>
    </div>

    <!-- Online Users -->
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-primary card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Online Now <i class="mdi mdi-access-point mdi-24px float-right"></i></h4>
          <h2 class="mb-5">{{ $stats['online_users'] }}</h2>
          <p class="card-text">Active in last 5 mins</p>
        </div>
      </div>
    </div>

    <!-- Deposits Today -->
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-info card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Deposits <i class="mdi mdi-cash-multiple mdi-24px float-right"></i></h4>
          <h2 class="mb-5">KSh {{ number_format($stats['deposits_today'], 2) }}</h2>
          <p class="card-text">Successful today</p>
        </div>
      </div>
    </div>

    <!-- Withdrawals Today -->
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-success card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Withdrawals <i class="mdi mdi-cash-refund mdi-24px float-right"></i></h4>
          <h2 class="mb-5">KSh {{ number_format($stats['withdrawals_today'], 2) }}</h2>
          <p class="card-text">Paid out today</p>
        </div>
      </div>
    </div>

    <!-- Total Bets Today -->
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-warning card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Total Bets <i class="mdi mdi-chart-line mdi-24px float-right"></i></h4>
          <h2 class="mb-5">KSh {{ number_format($stats['total_bets_today'], 2) }}</h2>
          <p class="card-text">Betting volume today</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- P2P Quick Alert -->
    <div class="col-12 grid-margin stretch-card">
      <div class="card bg-dark text-white shadow">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <h4 class="mb-0">Pending P2P Matchings</h4>
            <p class="mb-0 text-muted">Users currently waiting for peers</p>
          </div>
          <div class="text-end">
            <h2 class="mb-0 text-warning">{{ $stats['p2p_pending'] }}</h2>
            <a href="/manage_jet_secure/p2p/withdrawals" class="text-warning small text-decoration-none">View Details →</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 stretch-card grid-margin">
      <div class="card bg-gradient-secondary card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Total User 
          </h4>
          <h2 class="mb-5">{{count($user)}}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-4 stretch-card grid-margin">
      <div class="card bg-gradient-secondary card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Total Recharge
          </h4>
          <h2 class="mb-5">{{count($recharge)}}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-4 stretch-card grid-margin">
      <div class="card bg-gradient-secondary card-img-holder text-white">
        <div class="card-body">
          <img src="/aviatoradmin/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
          <h4 class="font-weight-normal mb-3">Total Withdrawal
          </h4>
          <h2 class="mb-5">{{count($withdrawal)}}</h2>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-7 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="clearfix">
            <h4 class="card-title float-left">Cash Flow (Last 7 Days)</h4>
            <div id="cash-flow-legend" class="rounded-legend legend-horizontal legend-top-right float-right"></div>
          </div>
          <div class="chart-container" style="position: relative; height:300px;">
            <canvas id="cashFlowChart" class="mt-4"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-5 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">New Users Growth</h4>
          <div class="chart-container" style="position: relative; height:300px;">
            <canvas id="userGrowthChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Top 10 High Rollers -->
    <div class="col-md-6 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Top 10 High Rollers (Bets)</h4>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th> User </th>
                  <th> Total Bet </th>
                  <th> Action </th>
                </tr>
              </thead>
              <tbody>
                @foreach($topHighRollers as $hr)
                <tr>
                  <td class="py-1">
                    <img src="/aviatoradmin/assets/images/faces/face{{ rand(1,4) }}.jpg" class="me-2" alt="image">
                    {{ $hr->user->name ?? 'User '.$hr->userid }}
                  </td>
                  <td> KSh {{ number_format($hr->total_bet, 2) }} </td>
                  <td>
                    <a href="/manage_jet_secure/user/edit/{{ $hr->userid }}" class="btn btn-sm btn-outline-primary">View</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Top 10 Depositors -->
    <div class="col-md-6 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Top 10 Depositors</h4>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th> User </th>
                  <th> Total Deposited </th>
                  <th> Action </th>
                </tr>
              </thead>
              <tbody>
                @foreach($topDepositors as $td)
                <tr>
                  <td class="py-1">
                    <img src="/aviatoradmin/assets/images/faces/face{{ rand(1,4) }}.jpg" class="me-2" alt="image">
                    {{ $td->user->name ?? 'User '.$td->userid }}
                  </td>
                  <td> KSh {{ number_format($td->total_deposited, 2) }} </td>
                  <td>
                    <a href="/manage_jet_secure/user/edit/{{ $td->userid }}" class="btn btn-sm btn-outline-success">View</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- <div class="row">
    <div class="col-md-5 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title text-white">Todo</h4>
          <div class="add-items d-flex">
            <input type="text" class="form-control todo-list-input" placeholder="What do you need to do today?">
            <button class="add btn btn-gradient-primary font-weight-bold todo-list-add-btn" id="add-task">Add</button>
          </div>
          <div class="list-wrapper">
            <ul class="d-flex flex-column-reverse todo-list todo-list-custom">
              <li>
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="checkbox" type="checkbox"> Meeting with Alisa </label>
                </div>
                <i class="remove mdi mdi-close-circle-outline"></i>
              </li>
              <li class="completed">
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="checkbox" type="checkbox" checked> Call John </label>
                </div>
                <i class="remove mdi mdi-close-circle-outline"></i>
              </li>
              <li>
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="checkbox" type="checkbox"> Create invoice </label>
                </div>
                <i class="remove mdi mdi-close-circle-outline"></i>
              </li>
              <li>
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="checkbox" type="checkbox"> Print Statements </label>
                </div>
                <i class="remove mdi mdi-close-circle-outline"></i>
              </li>
              <li class="completed">
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="checkbox" type="checkbox" checked> Prepare for presentation </label>
                </div>
                <i class="remove mdi mdi-close-circle-outline"></i>
              </li>
              <li>
                <div class="form-check">
                  <label class="form-check-label">
                    <input class="checkbox" type="checkbox"> Pick up kids from school </label>
                </div>
                <i class="remove mdi mdi-close-circle-outline"></i>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div> --}}
</div>
<!-- content-wrapper ends -->
@endsection

@section('js')
<script>
  (function($) {
    'use strict';
    $(function() {
      if ($("#cashFlowChart").length) {
        var ctx = document.getElementById('cashFlowChart').getContext("2d");
        var chartData = @json($chartData);

        var myChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Deposits',
                data: chartData.deposits,
                borderColor: '#1bcfb4',
                backgroundColor: 'rgba(27, 207, 180, 0.1)',
                borderWidth: 2,
                fill: true
              },
              {
                label: 'Withdrawals',
                data: chartData.withdrawals,
                borderColor: '#fe7c96',
                backgroundColor: 'rgba(254, 124, 150, 0.1)',
                borderWidth: 2,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                  callback: function(value) { return 'KSh ' + value; }
                }
              }]
            }
          }
        });
      }

      if ($("#userGrowthChart").length) {
        var ctx = document.getElementById('userGrowthChart').getContext("2d");
        var chartData = @json($chartData);

        var myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: chartData.labels,
            datasets: [{
              label: 'New Users',
              data: chartData.users,
              backgroundColor: '#9a55ff',
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                  stepSize: 1
                }
              }]
            }
          }
        });
      }
    });
  })(jQuery);
</script>
@endsection