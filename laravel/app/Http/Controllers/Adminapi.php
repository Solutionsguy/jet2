<?php

namespace App\Http\Controllers;

use App\Models\Bankdetail;
use App\Models\Bank_detail;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Hash;
use Illuminate\Http\Request;

class Adminapi extends Controller
{
    public function changepassword(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'userid' => 'required|exists:users,id',
            'newpassword' => 'required|string|min:6',
            'renewpassword' => 'required|same:newpassword',
        ], [
            'renewpassword.same' => 'The password confirmation does not match.',
            'newpassword.min' => 'Password must be at least 6 characters.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'title' => 'Validation Error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $update = User::where('id', $r->userid)->update([
            "password" => Hash::make($r->newpassword),
        ]);

        if ($update) {
            return response()->json([
                'status' => 1,
                'title' => "Success!!",
                'message' => "Password successfully updated!"
            ]);
        }

        return response()->json([
            'status' => 0,
            'title' => "Error!!",
            'message' => "Something went wrong while updating password!"
        ]);
    }
    public function edituser(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'userid' => 'required|exists:users,id',
            'newpassword' => 'required|string|min:6',
            'renewpassword' => 'required|same:newpassword',
        ], [
            'renewpassword.same' => 'The password confirmation does not match.',
            'newpassword.min' => 'Password must be at least 6 characters.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'title' => 'Validation Error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $update = User::where('id', $r->userid)->update([
            "password" => Hash::make($r->newpassword),
        ]);

        if ($update) {
            return response()->json([
                'status' => 1,
                'title' => "Success!!",
                'message' => "User password updated successfully!"
            ]);
        }

        return response()->json([
            'status' => 0,
            'title' => "Error!!",
            'message' => "Something went wrong while updating user password!"
        ]);
    }
    public function rechargeapproval($event, Request $r)
    {
        $response = array('status' => 0, 'title' => "Oops!!", 'message' => "Invalid Action!");
        $id = $r->id;
        $userid = $r->userid;
        $amount = $r->amount;
        if ($event == 'success') {
            $firstrecharge = Transaction::where('userid', $userid)->where('category', 'recharge')->where('status','1')->get();
            if (count($firstrecharge) == 0) {
                $currentUser = User::find($userid);
                if ($currentUser && $currentUser->promocode) {
                    $level1 = User::where('id', $currentUser->promocode)->first();
                    if ($level1) {
                        $level1amount = ($amount / 100 ) * setting('level1commission');
                        addwallet($level1->id, $level1amount);
                        addtransaction($level1->id, 'Level', date("ydmhsi"), 'credit', $level1amount, 'Level_bonus', 'Success', '1');

                        if ($level1->promocode) {
                            $level2 = User::where('id', $level1->promocode)->first();
                            if ($level2) {
                                $level2amount = ($amount / 100) * setting('level2commission');
                                addwallet($level2->id, $level2amount);
                                addtransaction($level2->id, 'Level', date("ydmhsi"), 'credit', $level2amount, 'Level_bonus', 'Success', '1');

                                if ($level2->promocode) {
                                    $level3 = User::where('id', $level2->promocode)->first();
                                    if ($level3) {
                                        $level3amount = ($amount / 100) * setting('level3commission');
                                        addwallet($level3->id, $level3amount);
                                        addtransaction($level3->id, 'Level', date("ydmhsi"), 'credit', $level3amount, 'Level_bonus', 'Success', '1');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $data = Transaction::where('id', $id)->update([
                "remark" => 'Success',
                "status" => '1',
            ]);
            addwallet($userid, $amount);
            $response = array('status' => 1, 'title' => "Success!!", 'message' => "Recharge successfully updated!");

        } elseif ($event == 'cancel') {
            $data = Transaction::where('id', $id)->update([
                "remark" => 'Cancle payment due to some issue',
                "status" => '2',
            ]);
            $response = array('status' => 1, 'title' => "Success!!", 'message' => "Recharge successfully updated!");
        }
        return response()->json($response);
    }
    public function withdrawalapproval($event, Request $r)
    {
        $response = array('status' => 0, 'title' => "Oops!!", 'message' => "Invalid Action!");
        $id = $r->id;
        $userid = $r->userid;
        $amount = $r->amount;
        if ($event == 'success') {
            $data = Transaction::where('id', $id)->update([
                "transactionno" => 'doltedaviator' . date("dmyhis"),
                "remark" => 'Success',
                "status" => '1',
            ]);
            $response = array('status' => 1, 'title' => "Success!!", 'message' => "Withdrawal successfully updated!");
        } elseif ($event == 'cancel') {
            $data = Transaction::where('id', $id)->update([
                "remark" => 'Cancle payment due to some issue',
                "status" => '2',
            ]);
            addwallet($userid, $amount);
            $response = array('status' => 1, 'title' => "Success!!", 'message' => "Withdrawal successfully updated!");
        }
        return response()->json($response);
    }
    public function userdelete(Request $r)
    {
        $response = array('status' => 0, 'title' => "Oops!!", 'message' => "Invalid Action!");
        $id = $r->id;
        User::where('id', $id)->delete();
        Wallet::where('userid', $id)->delete();
        Transaction::where('userid', $id)->delete();
        $response = array('status' => 1, 'title' => "Success!!", 'message' => "User successfully Deleted!");
        return response()->json($response);
    }
    public function payment_gateway(Request $r)
    {
        $status = false;
        $message = "Something went wrong!";
        $detail = Bankdetail::where('id', '1')->first();
        if ($detail) {
            $status = true;
            $data = array(
                'user_name' => $detail->account_holder_name,
                'mobile_no' => $detail->mobile_no,
                'upi_id' => $detail->upi_id,
                'account_number' => $detail->account_no,
                'ifsc_code' => $detail->ifsc_code,
                'bank_name' => $detail->bank_name,
                'barcode' => $detail->barcode,
            );
            $message = "";

        } else {
            $status = false;
            $data = array();
            $message = "Something wents wrong!";
        }
        $response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
    }

    public function editamountsetup(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'id' => 'required|exists:settings,id',
            'settingname' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'title' => 'Validation Error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $update = Setting::where('id', $r->id)->update([
            "category" => $r->settingname,
            "value" => $r->value,
        ]);
        
        if ($update) {
            return response()->json([
                'status' => 1,
                'title' => "Success!!",
                'message' => "Setting updated successfully!"
            ]);
        }
        
        return response()->json([
            'status' => 0,
            'title' => "Error!!",
            'message' => "Something went wrong while updating!"
        ]);
    }

    public function editbankdetail(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'holdername' => 'required|string|max:255',
            'mobile_no' => 'required|string|max:20',
            'upi_id' => 'required|string|max:255',
            'account_no' => 'required|string|max:50',
            'ifsccode' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'barcode' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'title' => 'Validation Error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $exist = Bankdetail::where('id', '1')->first();
        if ($exist) {
            if ($r->hasFile('barcode')) {
                $barcode = imageupload($r->file('barcode'), 'barcode', 'manage_jet_secure/bankdetail/')['filePath'];
            } else {
                $barcode = $exist->barcode;
            }
        } else {
            return response()->json([
                'status' => 0,
                'title' => 'Error!!',
                'message' => 'Bank detail record not found!'
            ]);
        }

        $update = Bankdetail::where('id', '1')->update([
            "account_holder_name" => $r->holdername,
            "mobile_no" => $r->mobile_no,
            "upi_id" => $r->upi_id,
            "account_no" => $r->account_no,
            "ifsc_code" => $r->ifsccode,
            "bank_name" => $r->bank_name,
            "barcode" => $barcode,
        ]);

        if ($update) {
            return response()->json([
                'status' => 1,
                'title' => "Success!!",
                'message' => "Bank details updated successfully!"
            ]);
        }

        return response()->json([
            'status' => 0,
            'title' => "Error!!",
            'message' => "Something went wrong while updating!"
        ]);
    }
    public function updatewallet(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'userid' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
        ], [
            'amount.numeric' => 'Please enter a valid numeric amount.',
            'amount.min' => 'Amount must be at least 0.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'title' => 'Validation Error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $userid = $r->userid;
        $amount = $r->amount;
        
        // Use atomic helper instead of direct update
        $new_balance = addwallet($userid, $amount, "+");
        
        if ($new_balance !== false) {
            // Add a transaction record for audit
            addtransaction($userid, "Admin", date("ydmhsi"), "credit", $amount, "Manual", "Updated by admin", "1");
            return response()->json([
                'status' => 1,
                'title' => "Success!!",
                'message' => "User Wallet successfully Updated!"
            ]);
        }

        return response()->json([
            'status' => 0,
            'title' => "Error!!",
            'message' => "Something went wrong while updating wallet!"
        ]);
    }

    public function depositNow(Request $r)
    {
        $trn = new Transaction;
        $trn->userid = user('id');
        $trn->platform = platform($r->payment_gateway_type);
        $trn->transactionno = $r->trn;
        $trn->type = 'credit';
        $trn->amount = $r->amount;
        $trn->category = 'recharge';
        $trn->remark = 'Processing';
        $trn->status = '0';
        if ($trn->save()) {
            return redirect('/deposit?msg=Success');
        }
    }
    public function withdrawal_query(Request $r)
    {
        // return $r->all();
        $trn = new Transaction;
        $trn->userid = user('id');
        $trn->platform = platform($r->payment_gateway_type);
        $trn->transactionno = '';
        $trn->type = 'debit';
        $trn->amount = $r->amount;
        $trn->category = 'withdraw';
        $trn->remark = 'Processing';
        $trn->status = '0';
        if ($trn->save()) {
            if (wallet(user('id'), 'num') > $r->amount) {
                addwallet(user('id'), $r->amount, '-');
            }
            $existbank = Bank_detail::where('userid', user('id'))->orderBy('id', 'desc')->first();
            if ($existbank) {
                Bank_detail::where('userid', user('id'))->update([
                    "bankname" => $r->bank_name,
                    "accountno" => $r->account_no,
                    "ifsccode" => $r->ifsc_code,
                    "upi_id" => $r->upi_id,
                    "mobile_no" => $r->mobile,
                ]);
                return redirect('/withdraw?msg=Success');
            } else {
                $bank = new Bank_detail;
                $bank->userid = user('id');
                $bank->bankname = $r->bank_name;
                $bank->accountno = $r->account_no;
                $bank->ifsccode = $r->ifsc_code;
                $bank->upi_id = $r->upi_id;
                $bank->mobile_no = $r->mobile;
                if ($bank->save()) {
                    return redirect('/withdraw?msg=Success');
                }
                return redirect('/withdraw?msg=error');
            }
        }
    }
}
