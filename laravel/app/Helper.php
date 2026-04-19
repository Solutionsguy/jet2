<?php

use App\Models\Gameresult;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Userbit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

function imageupload($file, $name, $path)
{
    $file_name = "";
    $file_type = "";
    $filePath = "";
    $size = "";

    if ($file) {
        $file_name = $file->getClientOriginalName();
        $file_type = $file->getClientOriginalExtension();
        $fileName = $name . "." . $file_type;
        Storage::disk('public')->put($path . $fileName, File::get($file));
        $filePath = "/" . 'storage/' . $path . $fileName;
    }
    return $file = [
        'fileName' => $file_name,
        'fileType' => $file_type,
        'filePath' => $filePath,
    ];
}
function datealgebra($date, $operator, $value, $format = "Y-m-d")
{
    if ($operator == "-") {
        $date = date_create($date);
        date_sub($date, date_interval_create_from_date_string($value));
        return date_format($date, $format);
    } elseif ($operator == "+") {
        $date = date_create($date);
        date_add($date, date_interval_create_from_date_string($value));
        return date_format($date, $format);
    }
}
function user($parameter,$id=null)
{
    if ($id == null) {
        $user = session()->get('userlogin');
        if (!$user) {
            $user = session()->get('adminlogin');
        }
        
        if ($user) {
            return is_object($user) ? $user->{$parameter} : ($user[$parameter] ?? null);
        }
        return null;
    }else{
        $data = User::where('id', $id)->first();
        if ($data) {
            return $data->{$parameter};
        }
        return 'User not found';
    }
}
function userdetail($id, $parameter)
{
    $data = User::where('id', $id)->first();
    if ($data) {
        return $data->{$parameter};
    }
    return 'User not found';
}
function admin($parameter)
{
    $admin = session()->get('adminlogin');
    if (!$admin) return 'Admin';
    
    try {
        if (is_object($admin)) {
            return $admin->$parameter ?? 'N/A';
        }
        if (is_array($admin)) {
            return $admin[$parameter] ?? 'N/A';
        }
    } catch (\Exception $e) {
        return 'Admin';
    }
    
    return 'Admin';
}
function wallet($userid, $type = "string")
{
    $amount = Wallet::where('userid', $userid)->first();
    if ($amount && $amount->amount > 0) {
        if ($type == "num") {
            return $amount->amount;
        } else {
            return number_format($amount->amount);
        }
    } else {
        return 0;
    }
}
function setting($parameter)
{
    $setting = Setting::where('category', $parameter)->first();
    if ($setting) {
        return $setting->value;
    }
    return null;
}

function currentid()
{
    $data = Gameresult::orderBy('id', 'desc')->first();
    if ($data) {
        return $data->id;
    } else {
        return 0;
    }
}
function dformat($date, $format)
{
    $strd = date_create($date);
    // if (date($format) == date_format($strd, $format)) {
    //     return "Today";
    // }
    return date_format($strd, $format);
}
function resultbyid($id)
{
    $data = Gameresult::where('id', $id)->first();
    if ($data && $data->result != 'pending' && $data->result != '') {
        return $data->result;
    }
    return 0;
}
function userbetdetail($id,$parameter)
{
    $data = Userbit::where('id', $id)->first();
    if ($data) {
        return $data->{$parameter};
    }
    return 0;
}
function addwallet($id, $amount, $symbol = "+")
{
    return Illuminate\Support\Facades\DB::transaction(function () use ($id, $amount, $symbol) {
        $wallet = Wallet::where('userid', $id)->lockForUpdate()->first();
        if ($wallet) {
            $amount = floatval($amount);
            if ($symbol == "+") {
                $wallet->increment('amount', $amount);
            } elseif ($symbol == "-") {
                // Prevent negative balance
                if ($wallet->amount < $amount) {
                    return false;
                }
                $wallet->decrement('amount', $amount);
            }
            return floatval($wallet->fresh()->amount);
        }
        return 0;
    });
}

function addfreebet($id, $amount, $symbol = "+")
{
    return Illuminate\Support\Facades\DB::transaction(function () use ($id, $amount, $symbol) {
        $wallet = Wallet::where('userid', $id)->lockForUpdate()->first();
        if ($wallet) {
            $amount = floatval($amount);
            if ($symbol == "+") {
                $multiplier = floatval(setting('freebet_wagering_multiplier') ?? 10);
                $extraWagering = $amount * $multiplier;
                
                $wallet->increment('freebet_amount', $amount);
                $wallet->increment('wagering_remaining', $extraWagering);
                $wallet->increment('initial_wagering_target', $extraWagering);
            } elseif ($symbol == "-") {
                if ($wallet->freebet_amount < $amount) {
                    $amount = $wallet->freebet_amount;
                }
                $wallet->decrement('freebet_amount', $amount);
            }
            return floatval($wallet->fresh()->freebet_amount);
        }
        return 0;
    });
}
function appvalidate($input)
{
    if ($input == '' || $input == null || $input == 0) {
        return 'Not found!';
    } else {
        return $input;
    }
}
function lastrecharge($id, $parameter)
{
    $data = Transaction::where('userid', $id)->where('type', 'credit')->where('category', 'recharge')->orderBy('id', 'desc')->first();
    if ($data) {
        return $data->{$parameter};
    }
    return false;
}
function status($code, $type)
{
    if ($type == 'recharge' || $type == 'deposit' || $type == 'withdraw') {
        if ($code == '0' || $code == 'pending' || $code === 0) {
            return array('color' => 'warning', 'name' => 'Pending');
        }
        if ($code == '1' || $code == 'success' || $code == 'paid' || $code === 1) {
            return array('color' => 'success', 'name' => 'Approved');
        }
        if ($code == '2' || $code == 'failed' || $code == 'cancelled' || $code === 2) {
            return array('color' => 'danger', 'name' => 'Cancel');
        }
        return array('color' => 'secondary', 'name' => 'Unknown');
    } elseif ($type == "user") {
        if ($code == 0) {
            return array('color' => 'danger', 'name' => 'Inactive');
        }
        if ($code == 1) {
            return array('color' => 'success', 'name' => 'Active');
        }
        if ($code == 2) {
            return array('color' => 'warning', 'name' => 'Pending');
        }
        return array('color' => 'secondary', 'name' => 'Unknown');
    }
    return array('color' => 'secondary', 'name' => 'Unknown');
}
// function bankdetail($userid,$parameter){
//     Bank_detail::where('userid',);
// }
function has_permission($permission)
{
    $admin = session()->get('adminlogin');
    if (!$admin) return false;

    // If it's the cached session object, we need to refresh it or check against it
    // For simplicity, we assume the session object might be stale, but the User model has the logic
    $user = User::find($admin->id);
    if (!$user || !$user->isadmin) return false;

    return $user->hasPermission($permission);
}

function platform($id)
{
    if ($id == 2) {
        return 'phonepay';
    } elseif ($id == 3) {
        return 'upi';
    } elseif ($id == 1) {
        return 'gpay';
    } elseif ($id == 9) {
        return 'imps';
    } elseif ($id == 6) {
        return 'netbanking';
    } else {
        return 'other';
    }
}

function addtransaction($userid, $platform, $transactionno, $type, $amount, $category, $remark, $status)
{
    $trn = new Transaction;
    $trn->userid = $userid;
    $trn->platform = $platform;
    $trn->transactionno = $transactionno;
    $trn->type = $type;
    $trn->amount = $amount;
    $trn->category = $category;
    $trn->remark = $remark;
    $trn->status = $status;
    if ($trn->save()) {
        return true;
    }
    return false;
}
