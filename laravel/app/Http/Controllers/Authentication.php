<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Hash;
use Illuminate\Http\Request;

class Authentication extends Controller
{
    public function login(Request $r)
    {
        try {
            $validated = $r->validate([
                'username' => 'required',
                'password' => 'required',
            ]);
            $data = "";
            $isSuccess = false;
            $message = "";
            $usernameexist = User::where(function($query) use ($r) {
                $query->where('mobile', $r->username)
                      ->orWhere('email', $r->username);
            })->first();
            if ($usernameexist) {
                if (\Illuminate\Support\Facades\Hash::check($r->password, $usernameexist->password)) {
                    $r->session()->put('userlogin', $usernameexist);
                    $message = "Login successful!";
                    $isSuccess = true;
                } else {
                    $message = "Incorrect Password!";
                }
            } else {
                $message = "Username not found!";
            }
            $res = array("data" => $data, "isSuccess" => $isSuccess, "message" => $message);
            return response()->json($res);
        } catch (\Exception $e) {
            \Log::error('User login exception: ' . $e->getMessage());
            return response()->json([
                'data' => "",
                'isSuccess' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $r)
    {
        $validated = $r->validate([
            'name' => 'required',
            'gender' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        $data = "";
        $isSuccess = false;
        $message = "Something went wrong!";
        
        // Check for duplicate Email/Mobile
        $olddata = User::where('email', $r->email)->orWhere('mobile', $r->mobile)->first();
        if ($olddata) {
            $message = "Duplicate Email Id/Mobile No., Please enter Unique Email id";
            return response()->json(array("data" => $data, "isSuccess" => $isSuccess, "message" => $message));
        }

        $promocode = $r->promocode;
        if ($promocode != '') {
            $existpromocode = User::where('id', $promocode)->first();
            if (!$existpromocode) {
                return response()->json(array("data" => array(), "isSuccess" => false, "message" => "Invalid Promocode"));
            }
        }

        $user = new User;
        $user->name = $r->name;
        $user->image = "/images/avtar/av-".rand(1,72).".png";
        $user->mobile = $r->mobile;
        $user->email = $r->email;
        $user->password = Hash::make($r->password);
        $user->currency = 'KES';
        $user->gender = $r->gender;
        $user->country = 'KE';
        $user->status = '1';
        $user->promocode = $promocode;

        if ($user->save()) {
            $wallet = new Wallet;
            $wallet->userid = $user->id;
            $wallet->amount = setting('initial_bonus') ?? 0;
            $wallet->save();
            
            $freebetBonus = setting('signup_freebet_bonus') ?? 0;
            if ($freebetBonus > 0) {
                addfreebet($user->id, $freebetBonus, "+");
            }

            $data = array("username" => $user->email, "password" => $r->password, "token" => csrf_token());
            $isSuccess = true;
            $message = "Registration successful!";
        }

        $res = array("data" => $data, "isSuccess" => $isSuccess, "message" => $message);
        return response()->json($res);
    }

    public function adminlogin(Request $r)
    {
        try {
            \Log::info('Admin login attempt: ' . $r->username);
            $validated = $r->validate([
                'username' => 'required',
                'password' => 'required',
            ]);
            $usernameexist = User::where(function($query) use ($r) {
                $query->where('mobile', $r->username)
                      ->orWhere('email', $r->username);
            })->where('isadmin', '1')->first();
            
            if ($usernameexist) {
                \Log::info('Admin user found: ' . $usernameexist->email);
                if (\Illuminate\Support\Facades\Hash::check($r->password, $usernameexist->password)) {
                    $r->session()->put('adminlogin', $usernameexist);
                    return response()->json(['status' => 1, 'title' => "Success!!", 'message' => "Login Successfully!"]);
                } else {
                    return response()->json(['status' => 0, 'title' => "Oops!!", 'message' => "Incorrect Password!"]);
                }
            } else {
                \Log::info('Admin user NOT found for: ' . $r->username);
                return response()->json(['status' => 0, 'title' => "Oops!!", 'message' => "Username does not exist or you are not an admin!"]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 0, 'title' => 'Validation Error', 'message' => 'Please fill all fields'], 422);
        } catch (\Exception $e) {
            \Log::error('Admin login exception: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'status' => 0,
                'title' => 'Oops!',
                'message' => 'Server Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }
}
