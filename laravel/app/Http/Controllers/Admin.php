<?php

namespace App\Http\Controllers;

use App\Models\Bankdetail;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Admin extends Controller
{
    public function login()
    {
        return view("admin.login");
    }
    public function dashboard()
    {
        $user = User::all();
        $recharge = Transaction::where('category', 'recharge')->get();
        $withdrawal = Transaction::where('category', 'withdraw')->get();

        // Today's Stats
        $today = \Carbon\Carbon::today();
        $fiveMinsAgo = \Carbon\Carbon::now()->subMinutes(5);
        $stats = [
            'new_users_today' => User::whereDate('created_at', $today)->count(),
            'online_users' => User::where('last_seen', '>=', $fiveMinsAgo)->count(),
            'deposits_today' => Transaction::where('category', 'recharge')
                ->where('status', '1')
                ->whereDate('created_at', $today)
                ->sum('amount'),
            'withdrawals_today' => Transaction::where('category', 'withdraw')
                ->where('status', '1')
                ->whereDate('created_at', $today)
                ->sum('amount'),
            'total_bets_today' => \App\Models\Userbit::whereDate('created_at', $today)->sum('amount'),
            'p2p_pending' => \App\Models\P2PWithdrawal::whereIn('status', ['searching', 'matched'])->count(),
        ];

        // 7-Day Chart Data
        $chartData = [
            'labels' => [],
            'deposits' => [],
            'withdrawals' => [],
            'users' => []
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $dateString = $date->format('M d');
            
            $chartData['labels'][] = $dateString;
            $chartData['deposits'][] = Transaction::where('category', 'recharge')
                ->where('status', '1')
                ->whereDate('created_at', $date)
                ->sum('amount');
            $chartData['withdrawals'][] = Transaction::where('category', 'withdraw')
                ->where('status', '1')
                ->whereDate('created_at', $date)
                ->sum('amount');
            $chartData['users'][] = User::whereDate('created_at', $date)->count();
        }

        // Leaderboards (Top 10)
        $topDepositors = Transaction::where('category', 'recharge')
            ->where('status', '1')
            ->select('userid', DB::raw('SUM(amount) as total_deposited'))
            ->groupBy('userid')
            ->orderBy('total_deposited', 'desc')
            ->limit(10)
            ->with('user')
            ->get();

        $topHighRollers = \App\Models\Userbit::select('userid', DB::raw('SUM(amount) as total_bet'))
            ->groupBy('userid')
            ->orderBy('total_bet', 'desc')
            ->limit(10)
            ->with('user')
            ->get();

        return view("admin.dashboard", [
            "user" => $user,
            "recharge" => $recharge,
            "withdrawal" => $withdrawal,
            "stats" => $stats,
            "chartData" => $chartData,
            "topDepositors" => $topDepositors,
            "topHighRollers" => $topHighRollers
        ]);
    }
    public function userlist()
    {
        $userlist = User::where('isadmin', null)->orderBy('id','desc')->get();
        return view("admin.userlist", compact("userlist"));
    }
    public function useredit($id)
    {
        $user = User::where('isadmin', null)->where('id', $id)->first();
        return view("admin.useredit", compact("user"));
    }
    public function chagepassword()
    {
        return view('admin.changepassword');
    }
    public function rechargehistory()
    {
        $history = Transaction::where('category', 'recharge')->where('type', 'credit')->orderBy('id','desc')->get();
        $title = 'Recharge Hitory';
        return view('admin.rechargehistory', [
            'history' => $history,
            'title' => $title,
        ]);
    }
    public function withdrawalhistory()
    {
        $history = Transaction::where('category', 'withdraw')->where('type', 'debit')->join('bank_details', 'transactions.userid', '=', 'bank_details.userid')->select('transactions.*','bank_details.accountno','bank_details.ifsccode','bank_details.branchname','bank_details.upi_id','bank_details.mobile_no')->orderBy('transactions.id','desc')->get();
        $title = 'Withdrawal Hitory';
        return view('admin.withdrawhistory', [
            'history' => $history,
            'title' => $title,
        ]);
    }
    public function amountsetup($id = null)
    {
        $specificdata = null;
        $settings = Setting::get();
        $title = 'Withdrawal Hitory';
        if ($id != null) {
            $specificdata = Setting::where('id', $id)->first();
        }
        return view('admin.amountsetup', [
            'setting' => $settings,
            'id' => $id,
            'specificdata' => $specificdata,
        ]);
    }
    public function bankdetail()
    {
        $specificdata = null;
        $title = 'Bank Detail';
        $specificdata = Bankdetail::where('id', '1')->first();
        return view('admin.bankdetail', [
            'bank' => $specificdata,
        ]);
    }
    public function logout()
    {
        if (session()->has('adminlogin')) {
            session()->forget('adminlogin');
        }
        return redirect('/admin');
    }
}
