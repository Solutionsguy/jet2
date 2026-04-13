<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Carbon\Carbon;

class Authentication extends Controller
{
    public function forgotPassword(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'username' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['isSuccess' => false, 'message' => 'Email or Phone is required.']);
        }

        $user = User::where('email', $r->username)->orWhere('mobile', $r->username)->first();

        if (!$user) {
            return response()->json(['isSuccess' => false, 'message' => 'User not found.']);
        }

        if (!$user->email) {
            return response()->json(['isSuccess' => false, 'message' => 'This user does not have an email associated.']);
        }

        // Generate 6 digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in database
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $otp, 'created_at' => now()]
        );

        // Send Email
        try {
            Mail::to($user->email)->send(new PasswordResetMail($otp));
            return response()->json([
                'isSuccess' => true, 
                'message' => 'Reset code sent to your email.',
                'data' => ['email' => $user->email]
            ]);
        } catch (\Exception $e) {
            \Log::error('Mail Error: ' . $e->getMessage());
            return response()->json(['isSuccess' => false, 'message' => 'Failed to send email. Please check your SMTP settings.']);
        }
    }

    public function verifyOtp(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['isSuccess' => false, 'message' => 'All fields are required.']);
        }

        $record = DB::table('password_resets')
            ->where('email', $r->email)
            ->where('token', $r->otp)
            ->where('created_at', '>', now()->subMinutes(15))
            ->first();

        if (!$record) {
            return response()->json(['isSuccess' => false, 'message' => 'Invalid or expired code.']);
        }

        return response()->json(['isSuccess' => true, 'message' => 'Code verified.']);
    }

    public function resetPassword(Request $r)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'password' => 'required|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['isSuccess' => false, 'message' => $validator->errors()->first()]);
        }

        // Verify OTP again for safety
        $record = DB::table('password_resets')
            ->where('email', $r->email)
            ->where('token', $r->otp)
            ->first();

        if (!$record) {
            return response()->json(['isSuccess' => false, 'message' => 'Security check failed.']);
        }

        // Update user password
        User::where('email', $r->email)->update([
            'password' => Hash::make($r->password)
        ]);

        // Delete the used OTP
        DB::table('password_resets')->where('email', $r->email)->delete();

        return response()->json(['isSuccess' => true, 'message' => 'Password reset successful. You can now login.']);
    }

    public function login(Request $r)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
                'username' => 'required',
                'password' => 'required',
            ], [
                'username.required' => 'Email or Mobile number is required.',
                'password.required' => 'Password is required.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "isSuccess" => false, 
                    "message" => $validator->errors()->first()
                ]);
            }

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
        $validator = \Illuminate\Support\Facades\Validator::make($r->all(), [
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'mobile' => 'required|string|unique:users,mobile',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'promocode' => 'nullable|exists:users,id'
        ], [
            'email.unique' => 'This email is already registered.',
            'mobile.unique' => 'This mobile number is already registered.',
            'password.min' => 'Password must be at least 6 characters.',
            'promocode.exists' => 'The provided referral code is invalid.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "isSuccess" => false, 
                "message" => $validator->errors()->first(),
                "errors" => $validator->errors()
            ]);
        }

        $data = "";
        $isSuccess = false;
        $message = "Something went wrong!";

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
        $user->demo_balance = setting('demo_balance') ?? 1000;

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
