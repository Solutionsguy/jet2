<?php

namespace App\Http\Controllers;

use App\Models\Gameresult;
use App\Models\Setting;
use App\Models\Userbit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Gamesetting extends Controller
{
    
    public function crash_plane()
    {
        return 1;
    }
    public function game_existence(Request $r)
    {
        $event = $r->event;
        if ($event == "check") {
            $new = Setting::where('category', 'game_status')->where('value', '0')->first();
            
            if ($new || (session()->has('gamegenerate') && session()->get('gamegenerate') == 1)) {
                return array('data'=>true);
            }else{
                return array('data'=>false);
            }
            return array('data'=>false);
        }
    }
    public function new_game_generated(Request $r)
    {
        $new = Setting::where('category', 'game_status')->update(['value' => '0']);
        $r->session()->put('gamegenerate','1');
        $gameId = currentid();
        
        // Broadcast to socket for synchronization
        $targetMultiplier = $r->input('targetMultiplier', rand(8, 11) / 10);
        
        return response()->json(array(
            "id" => $gameId,
            "targetMultiplier" => $targetMultiplier,
            "broadcast" => true // Signal to frontend to emit socket event
        ));
    }
    
    public function increamentor(Request $r)
    {
        $gamestatusdata = Setting::where('category', 'game_status')->first();
        $res = 0;
        if($gamestatusdata){
                
        $totalbet = Userbit::where('gameid',currentid())->count();
        $totalamount = Userbit::where('gameid',currentid())->sum('amount');
        if ($totalbet == 0) {
            $res =  rand(8,11);
        }else{
            $randomresult = array(1.1,1.1,1.2,1.3,1.4,1.5,1.6,1.7,1.8,1.9);
            $res = $randomresult[rand(0,8)];
            
        }
        
                $status = true;
                $result = $res;
                $response = array('status'=>$status,'result'=>$result);
        return response()->json($response);
        }
    }
    // public function increamentor(Request $r)
    // {
    //     // return 1.7;
    //     $totalbet = Userbit::where('gameid',currentid())->count();
    //     $totalamount = Userbit::where('gameid',currentid())->sum('amount');
    //     if ($totalbet == 0) {
    //         return rand(8,11);
    //     }else{
    //         $randomresult = array(1.1,1.1,1.2,1.3,1.4,1.5,1.6,1.7,1.8,1.9);
    //         $res = $randomresult[rand(0,8)];
    //         if (session()->has('result')) {
    //             return session()->get('result');
    //         }
    //         $r->session()->put('result',$res);
    //         return $res;
    //     }
    //     return rand(setting('start_range_game_timer')*10, setting('end_range_game_timer')*10) / 10;
    // }
    public function game_over(Request $r)
    {
        $r->session()->forget('result');
        $result = Gameresult::where('id', currentid())->update([
            "result" => number_format($r->last_time, 2),
        ]);
        $alluserbit = Userbit::where('gameid', currentid())->where('status', 0)->get();
        foreach ($alluserbit as $key) {
			if(floatval($r->last_time) <= 1.20){
			$result = 0;
		    }else{
			$result = $r->last_time;
			}
            $finalamount = floatval($key->amount) * floatval($result);
            Userbit::where('id', $key->id)->update(["status"=> 1]);
            // addwallet($key->userid,$finalamount);
        }
        $new = Setting::where('category', 'game_status')->update(['value' => '0']);
        $r->session()->put('gamegenerate','0');
        $result = new Gameresult;
        $result->result = "pending";
        $result->save();
        return wallet(user('id'));
    }

    public function betNow(Request $r)
    {
        $status = false;
        $message = "Something went wrong!";
        $returnbets = array();
        $betData = array();
        $data = array();
        $new_balance = 0;
        
        // Validate user is logged in
        $userId = user('id');
        if (!$userId) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "User not logged in");
            return response()->json($response);
        }
        
        // Validate bets array exists
        if (!$r->all_bets || count($r->all_bets) == 0) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "No bets provided");
            return response()->json($response);
        }
        
        // Get current wallet balance
        $current_balance = floatval(wallet($userId, 'num'));
        
        for($i=0; $i < count($r->all_bets); $i++){
            $bet_amount = floatval($r->all_bets[$i]['bet_amount']);
            
            // Validate bet amount
            if ($bet_amount <= 0) {
                $status = false;
                $message = "Invalid bet amount";
                break;
            }
            
            // Check if user has enough balance (bet_amount should be <= wallet balance)
            if ($bet_amount <= $current_balance) {
                // FIRST deduct the bet amount from wallet
                $new_balance = addwallet($userId, $bet_amount, "-");
                
                // Update current_balance for next iteration (multiple bets)
                $current_balance = $new_balance;
                
                // THEN save the bet record
                $result = new Userbit;
                $result->userid = $userId;
                $result->amount = $bet_amount;
                $result->type = $r->all_bets[$i]['bet_type'] ?? 0;
                $result->gameid = currentid();
                $result->section_no = $r->all_bets[$i]['section_no'] ?? 0;
                $result->status = 0; // 0 = active bet, 1 = cashed out/completed
                
                if ($result->save()) {
                    $status = true;
                    array_push($returnbets, [
                        "bet_id" => $result->id,
                    ]);
                    
                    // Prepare data for socket broadcast
                    $betData[] = [
                        "betId" => $result->id,
                        "odapuId" => $userId,
                        "userId" => $userId,
                        "username" => user('username') ?? user('name') ?? 'Player',
                        "odapu" => user('username') ?? user('name') ?? 'Player',
                        "amount" => $bet_amount,
                        "avatar" => user('image') ?? '/images/avtar/av-1.png',
                        "betType" => $r->all_bets[$i]['bet_type'] ?? 0,
                        "sectionNo" => $r->all_bets[$i]['section_no'] ?? 0,
                        "status" => "active"
                    ];
                    
                    $message = "";
                } else {
                    // If save failed, refund the deducted amount
                    addwallet($userId, $bet_amount, "+");
                    $new_balance = $current_balance + $bet_amount; // Restore balance
                    $status = false;
                    $message = "Failed to save bet to database";
                    break;
                }
            } else {
                $status = false;
                $message = "Insufficient funds! Balance: " . number_format($current_balance, 2) . ", Bet: " . number_format($bet_amount, 2);
                break; // Stop processing more bets if insufficient funds
            }
        }
        
        // Build final response data - use the latest balance
        $final_balance = $status ? $new_balance : floatval(wallet($userId, 'num'));
        $data = array(
            "wallet_balance" => round($final_balance, 2),
            "return_bets" => $returnbets,
            "socket_data" => $betData
        );
        
        $response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
    }
    public function currentlybet()
    {
        $allbets = Userbit::where("gameid", currentid())->join('users','users.id','=','userbits.userid')->get();
        $currentGameBet = $allbets;
        for ($i=0; $i < rand(400,900); $i++) { 
            $currentGameBet[]=array(
                "userid" => rand(10000,50000),
                "amount" => rand(999,9999),
				"image"  => "/images/avtar/av-".rand(1,72).".png"
            );
        }
        $currentGame = array("id"=>currentid());
        $currentGameBetCount = count($currentGameBet);
        $response = array("currentGame" => $currentGame, "currentGameBet" => $currentGameBet, "currentGameBetCount" => $currentGameBetCount);
        return response()->json($response);
    }
    public function my_bets_history(){
        $userid = user('id');
        $userbets = Userbit::where("userid", $userid)->where('status',1)->where('created_at', '>=', Carbon::today()->toDateString())->orderBy('id','desc')->get();
        return response()->json($userbets);
    }
	public function cashout(Request $r){
		$game_id = $r->game_id;
		$bet_id = $r->bet_id;
		$win_multiplier = floatval($r->win_multiplier);
		$cash_out_amount = 0;
		$status = false;
        $message = "";
        $data = array();
        
        // Validate user is logged in
        $userId = user('id');
        if (!$userId) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "User not logged in");
            return response()->json($response);
        }
        
        // Validate bet_id
        if (!$bet_id) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "Bet ID is required");
            return response()->json($response);
        }
        
        // Get the bet details
        $bet = Userbit::where('id', $bet_id)->first();
        
        if (!$bet) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "Bet not found");
            return response()->json($response);
        }
        
        // Verify the bet belongs to the current user
        if ($bet->userid != $userId) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "This bet does not belong to you");
            return response()->json($response);
        }
        
        $bet_amount = floatval($bet->amount);
        $bet_status = intval($bet->status);
        
        if ($bet_status == 1) {
            // Already cashed out
            $response = array("isSuccess" => false, "data" => array(), "message" => "Already cashed out");
            return response()->json($response);
        }
        
        // Validate multiplier
        if ($win_multiplier < 1.00) {
            $win_multiplier = 1.00; // Minimum multiplier is 1.00 (break even)
        }
        
        // Calculate cashout amount - player gets bet_amount * multiplier when they cash out
        $cash_out_amount = $bet_amount * $win_multiplier;
        
        // Add winnings to wallet and get new balance
		$new_balance = addwallet($userId, $cash_out_amount, "+");
		
		// Update bet status
        Userbit::where('id', $bet_id)->update([
            "status" => 1,
            "cashout_multiplier" => $win_multiplier
        ]);
        
        $status = true;
        $message = "Cashed out successfully at " . number_format($win_multiplier, 2) . "x";
        
		$data = array(
            "wallet_balance" => round($new_balance, 2),
            "cash_out_amount" => round($cash_out_amount, 2),
            "socket_data" => [
                "betId" => $bet_id,
                "userId" => $userId,
                "username" => user('username') ?? user('name') ?? 'Player',
                "multiplier" => $win_multiplier,
                "winAmount" => round($cash_out_amount, 2)
            ]
        );
        
		$response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
	}
	
	/**
	 * Auto cash-out endpoint for socket server
	 * Called by the socket server when a player's auto cash-out multiplier is reached
	 * This works even when the player's browser tab is inactive
	 */
	public function autoCashout(Request $r){
		$bet_id = $r->bet_id;
		$win_multiplier = floatval($r->win_multiplier);
		$status = false;
        $message = "";
        $data = array();
        
        // Validate bet_id
        if (!$bet_id) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "Bet ID is required");
            return response()->json($response);
        }
        
        // Get the bet details
        $bet = Userbit::where('id', $bet_id)->first();
        
        if (!$bet) {
            $response = array("isSuccess" => false, "data" => array(), "message" => "Bet not found");
            return response()->json($response);
        }
        
        $userId = $bet->userid;
        $bet_amount = floatval($bet->amount);
        $bet_status = intval($bet->status);
        
        if ($bet_status == 1) {
            // Already cashed out
            $response = array("isSuccess" => false, "data" => array(), "message" => "Already cashed out");
            return response()->json($response);
        }
        
        // Validate multiplier
        if ($win_multiplier < 1.00) {
            $win_multiplier = 1.00;
        }
        
        // Calculate cashout amount
        $cash_out_amount = $bet_amount * $win_multiplier;
        
        // Add winnings to wallet
		$new_balance = addwallet($userId, $cash_out_amount, "+");
		
		// Update bet status
        Userbit::where('id', $bet_id)->update([
            "status" => 1,
            "cashout_multiplier" => $win_multiplier
        ]);
        
        $status = true;
        $message = "Auto cashed out at " . number_format($win_multiplier, 2) . "x";
        
        // Get user details for response
        $user = User::find($userId);
        
		$data = array(
            "wallet_balance" => round($new_balance, 2),
            "cash_out_amount" => round($cash_out_amount, 2),
            "user_id" => $userId,
            "username" => $user ? ($user->username ?? $user->name ?? 'Player') : 'Player',
            "multiplier" => $win_multiplier
        );
        
        \Log::info("Auto cash-out processed: Bet {$bet_id}, User {$userId}, Amount {$cash_out_amount}, Multiplier {$win_multiplier}x");
        
		$response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
	}

	public function cronjob(){
	    //0 = Game end & statrting soon
	    //1 = Game start & and is in proccess
	    $gamestatusdata = Setting::where('category', 'game_status')->first();
	    $game_status = 0;
	    if($gamestatusdata){
	        $game_status = $gamestatusdata->value;
	    }
	    if($game_status == 1){
	    $last_start_time = Setting::where('category', 'game_start_time')->first()->value;
	    $last_till_time = Setting::where('category', 'game_between_time')->first()->value;
	    $bothdifference = datealgebra($last_start_time, '+', ($last_till_time/1000).' seconds', $format = "Y-m-d h:i:s");
	    if(strtotime(date('Y-m-d h:i:s')) >= strtotime($bothdifference)){
	        $gamestatusdata = Setting::where('category', 'game_status')->update([
	             "value"  => 0
	             ]);
	    }
	    }elseif($game_status == 0){
	         $gamestatusdata = Setting::where('category', 'game_status')->update(["value"  => 1]);
	       //  $gamestatusdata = Setting::where('category', 'game_start_time')->update(["value"  => date('Y-m-d h:i:s')]);
	       //  $gamestatusdata = Setting::where('category', 'game_between_time')->update(["value"  => 5000]);
	    }else{}
	}
	
	/**
	 * Get bet list for a previous game round
	 * Used when switching to "Previous Hand" tab in the sidebar
	 */
	public function previous_game_bet_list(Request $r)
	{
	    $status = false;
	    $data = array();
	    $message = "";
	    
	    $game_id = $r->game_id;
	    
	    if ($game_id) {
	        // Get the previous game ID (one before the provided game_id)
	        $previousGame = Gameresult::where('id', '<', $game_id)
	            ->orderBy('id', 'desc')
	            ->first();
	        
	        if ($previousGame) {
	            // Get all bets for the previous game
	            $allbets = Userbit::where("gameid", $previousGame->id)
	                ->join('users', 'users.id', '=', 'userbits.userid')
	                ->select('userbits.*', 'users.name', 'users.username', 'users.image')
	                ->get();
	            
	            $betList = [];
	            foreach ($allbets as $bet) {
	                $betList[] = [
	                    "betId" => $bet->id,
	                    "odapuId" => $bet->userid,
	                    "odapu" => $bet->username ?? $bet->name ?? 'Player',
	                    "amount" => $bet->amount,
	                    "avatar" => $bet->image ?? '/images/avtar/av-' . rand(1, 72) . '.png',
	                    "cashout_multiplier" => $bet->cashout_multiplier,
	                    "status" => $bet->status
	                ];
	            }
	            
	            // Add some fake bets for display (like in currentlybet)
	            for ($i = 0; $i < rand(50, 150); $i++) {
	                $fakeMultiplier = rand(0, 1) ? (rand(100, 300) / 100) : null;
	                $betList[] = [
	                    "betId" => rand(100000, 999999),
	                    "odapuId" => rand(10000, 50000),
	                    "odapu" => 'd***' . rand(100, 999),
	                    "amount" => rand(100, 5000),
	                    "avatar" => '/images/avtar/av-' . rand(1, 72) . '.png',
	                    "cashout_multiplier" => $fakeMultiplier,
	                    "status" => 1
	                ];
	            }
	            
	            $status = true;
	            $data = [
	                "bet_list" => $betList,
	                "bet_counts" => count($betList),
	                "win_multi" => $previousGame->result ?? 1.00
	            ];
	        } else {
	            $message = "No previous game found";
	        }
	    } else {
	        $message = "Game ID is required";
	    }
	    
	    $response = array("isSuccess" => $status, "data" => $data, "message" => $message);
	    return response()->json($response);
	}
}























