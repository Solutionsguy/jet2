<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Authentication;
use App\Http\Controllers\Gamesetting;
use App\Http\Controllers\Pages;
use App\Http\Controllers\Userdetail;
use App\Http\Controllers\Adminapi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */
Route::get('/storagelink', function () {
	$target = '/home/u558340823/domains/thixpro.in/public_html/aviator/laravel/storage/app/public/';
   $shortcut = '/home/u558340823/domains/thixpro.in/public_html/aviator/storage/';
   symlink($target, $shortcut);
    dd('storage link successfully');
});
Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('optimize');
    dd('Cache cleared successfully');
});
Route::get('/', [Pages::class, "welcome"]);
Route::get('/dashboard', [Pages::class, "welcome"]);
Route::get('/register', function () {
    return view('welcome');
});
Route::get('/login', function () {
    return view('welcome');
});
// Auth Login
Route::post('/auth/login', [Authentication::class, "login"]);
Route::post('/auth/register', [Authentication::class, "register"]);
Route::get('/is_login', [Userdetail::class, "is_login"]);
Route::get('/game-cron', [Gamesetting::class, "cronjob"]);

// Password Reset Routes
Route::post('/forgot_password_post', [Authentication::class, "forgotPassword"]);
Route::post('/verify_otp', [Authentication::class, "verifyOtp"]);
Route::post('/reset_password_post', [Authentication::class, "resetPassword"]);

// Auth Admin Login
Route::post('/auth/manage_jet_secure/login', [Authentication::class, "adminlogin"]);

// Admin Login
Route::get('/manage_jet_secure', [Admin::class, "login"]);
Route::group(['prefix' => 'manage_jet_secure', 'middleware' => ['isAdmin']], function () {

    Route::get('/dashboard', [Admin::class, "dashboard"]);
    Route::get('/change-password', [Admin::class, "chagepassword"]);
    
    // User Management
    Route::middleware(['permission:view_users'])->group(function () {
        Route::get('/user-list', [Admin::class, "userlist"]);
        Route::get('/user/edit/{id}', [Admin::class, "useredit"]);
    });

    // Recharge/Deposit Management
    Route::middleware(['permission:manage_deposits'])->group(function () {
        Route::get('/recharge-history', [Admin::class, "rechargehistory"]);
    });

    // Withdrawal Management
    Route::middleware(['permission:manage_withdrawals'])->group(function () {
        Route::get('/withdrawal-history', [Admin::class, "withdrawalhistory"]);
    });

    // Game/System Settings
    Route::middleware(['permission:game_settings'])->group(function () {
        Route::get('/amount-setup/{id?}', [Admin::class, "amountsetup"]);
        Route::get('/bank-detail', [Admin::class, "bankdetail"]);
    });

    // Role & Admin Management
    Route::middleware(['permission:full_access'])->group(function () {
        Route::get('/roles', [\App\Http\Controllers\AdminRoleController::class, 'rolesIndex']);
        Route::post('/roles', [\App\Http\Controllers\AdminRoleController::class, 'roleStore']);
        Route::post('/roles/{id}', [\App\Http\Controllers\AdminRoleController::class, 'roleUpdate']);
        
        Route::get('/sub-admins', [\App\Http\Controllers\AdminRoleController::class, 'subAdminsIndex']);
        Route::post('/sub-admins', [\App\Http\Controllers\AdminRoleController::class, 'subAdminStore']);
    });
    
    // Rain Management Routes
    Route::prefix('rain')->middleware(['permission:manage_rain'])->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminRainController::class, 'index']);
        Route::post('/create', [\App\Http\Controllers\AdminRainController::class, 'createSupportRain']);
        Route::get('/history', [\App\Http\Controllers\AdminRainController::class, 'getRainHistory']);
        Route::get('/analytics', [\App\Http\Controllers\AdminRainController::class, 'getRainAnalytics']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\AdminRainController::class, 'cancelRain']);
        Route::get('/{id}/participants', [\App\Http\Controllers\AdminRainController::class, 'getRainParticipants']);
        
        // Auto-Rain Settings
        Route::post('/auto-settings', [\App\Http\Controllers\AdminRainController::class, 'updateAutoRainSettings']);
        Route::post('/auto-trigger', [\App\Http\Controllers\AdminRainController::class, 'triggerAutoRain']);
    });
    
    // Freebet Management Routes
    Route::prefix('freebet')->middleware(['permission:manage_freebets'])->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminWalletController::class, 'freebetIndex']);
        Route::post('/add', [\App\Http\Controllers\AdminWalletController::class, 'addFreebet']);
        Route::post('/remove', [\App\Http\Controllers\AdminWalletController::class, 'removeFreebet']);
        Route::post('/bulk', [\App\Http\Controllers\AdminWalletController::class, 'bulkAddFreebet']);
        Route::get('/stats', [\App\Http\Controllers\AdminWalletController::class, 'getFreebetStats']);
        Route::get('/user/{userId}/history', [\App\Http\Controllers\AdminWalletController::class, 'getUserFreebetHistory']);
    });

    // Chat Management Routes
    Route::prefix('chat-management')->middleware(['permission:manage_chat'])->group(function () {
        Route::get('/', [\App\Http\Controllers\ChatController::class, 'management'])->name('admin.chat.management');
        Route::post('/approve/{id}', [\App\Http\Controllers\ChatController::class, 'approveMessage']);
        Route::post('/disapprove/{id}', [\App\Http\Controllers\ChatController::class, 'disapproveMessage']);
        Route::post('/update/{id}', [\App\Http\Controllers\ChatController::class, 'updateMessage']);
        Route::post('/delete/{id}', [\App\Http\Controllers\ChatController::class, 'deleteMessage']);
        Route::post('/auto-approve', [\App\Http\Controllers\ChatController::class, 'updateAutoApproveSettings']);
    });
    
    // P2P Management Routes
    Route::prefix('p2p')->middleware(['permission:manage_p2p'])->group(function () {
        Route::get('/', function() { return redirect()->route('admin.p2p.peers'); });
        Route::get('/peers', [\App\Http\Controllers\AdminP2PController::class, 'index'])->name('admin.p2p.peers');
        Route::post('/peers/store', [\App\Http\Controllers\AdminP2PController::class, 'storePeer'])->name('admin.p2p.peers.store');
        Route::post('/peers/update/{id}', [\App\Http\Controllers\AdminP2PController::class, 'updatePeer'])->name('admin.p2p.peers.update');
        Route::get('/peers/delete/{id}', [\App\Http\Controllers\AdminP2PController::class, 'deletePeer'])->name('admin.p2p.peers.delete');
        Route::get('/peers/toggle/{id}', [\App\Http\Controllers\AdminP2PController::class, 'toggleStatus'])->name('admin.p2p.peers.toggle');
        Route::get('/withdrawals', [\App\Http\Controllers\AdminP2PController::class, 'withdrawalHistory'])->name('admin.p2p.withdrawals');
        Route::get('/withdrawals/approve/{id}', [\App\Http\Controllers\AdminP2PController::class, 'approveWithdrawal'])->name('admin.p2p.withdrawals.approve');
        Route::get('/withdrawals/reject/{id}', [\App\Http\Controllers\AdminP2PController::class, 'rejectWithdrawal'])->name('admin.p2p.withdrawals.reject');
    });

    Route::group(['prefix' => 'api/'], function () {
        Route::post('/changepassword', [Adminapi::class, "changepassword"]);
        
        Route::post('/edituser', [Adminapi::class, "edituser"])->middleware('permission:edit_users');
        Route::post('/recharge/{event}', [Adminapi::class, "rechargeapproval"])->middleware('permission:manage_deposits');
        Route::post('/withdraw/{event}', [Adminapi::class, "withdrawalapproval"])->middleware('permission:manage_withdrawals');
        Route::post('/user/delete', [Adminapi::class, "userdelete"])->middleware('permission:edit_users');
        Route::post('/editamountsetup', [Adminapi::class, "editamountsetup"])->middleware('permission:game_settings');
        Route::post('/bankdetail', [Adminapi::class, "editbankdetail"])->middleware('permission:game_settings');
        Route::post('/updatewallet', [Adminapi::class, "updatewallet"])->middleware('permission:edit_users');
    });

    // Xaxino Game Management
    Route::controller(App\Http\Controllers\Admin\AdminGameController::class)->prefix('games')->group(function () {
        Route::get('/', 'index')->name('admin.game.index');
        Route::get('/edit/{id}', 'edit')->name('admin.game.edit');
        Route::post('/update/{id}', 'update')->name('admin.game.update');
        Route::post('/status/{id}', 'status')->name('admin.game.status');
        Route::post('/keno-update/{id}', 'kenoUpdate')->name('admin.game.keno.update');
        Route::get('/log', 'gameLog')->name('admin.game.log');
        Route::post('/chance-create/{alias?}', 'chanceCreate')->name('admin.game.chance.create');
    });

    // Category Management
    Route::controller(App\Http\Controllers\Admin\AdminCategoryController::class)->prefix('categories')->group(function () {
        Route::get('/', 'index')->name('admin.category.index');
        Route::post('/store', 'store')->name('admin.category.store');
        Route::post('/update/{id}', 'update')->name('admin.category.update');
        Route::post('/status/{id}', 'status')->name('admin.category.status');
        Route::get('/delete/{id}', 'delete')->name('admin.category.delete');
    });

    Route::get('/logout', [Admin::class, "logout"]);
});

Route::group(['middleware' => ['isUser', 'throttle:60,1']], function () {

    Route::get('/profile', [Userdetail::class, "profile"]);
    Route::get('/crash', [Pages::class, "aviator"]);
    
    // Chat routes (web auth)
    Route::get('/chat/messages', [App\Http\Controllers\ChatController::class, 'getMessages']);
    Route::post('/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage']);
    Route::delete('/chat/message/{id}', [App\Http\Controllers\ChatController::class, 'deleteMessage']);
    
    // Rain/Giveaway routes
    Route::post('/rain/create', [App\Http\Controllers\RainController::class, 'createRain']);
    Route::get('/rain/active', [App\Http\Controllers\RainController::class, 'getActiveRain']);
    Route::get('/rain/{id}', [App\Http\Controllers\RainController::class, 'getRain']);
    Route::post('/rain/join', [App\Http\Controllers\RainController::class, 'joinRain']);
    Route::post('/rain/{id}/distribute', [App\Http\Controllers\RainController::class, 'distributeRain']);
    
    Route::get('/deposit', [Pages::class, 'deposit']);
    Route::get('/amount-transfer', [Pages::class, "amount_transfer"]);
    
    // Paystack Payment Routes (M-Pesa via Paystack)
    Route::prefix('paystack')->group(function () {
        Route::post('/mpesa/initialize', [\App\Http\Controllers\PaystackController::class, 'initializeMpesaDeposit']);
        Route::post('/mpesa/withdraw', [\App\Http\Controllers\PaystackController::class, 'initializeMpesaWithdrawal']);
        Route::post('/initialize', [\App\Http\Controllers\PaystackController::class, 'initializeDeposit']);
        Route::get('/callback', [\App\Http\Controllers\PaystackController::class, 'handleCallback'])->name('paystack.callback');
    });

    Route::prefix('p2p')->group(function () {
        Route::post('/search', [\App\Http\Controllers\P2PController::class, 'startSearch']);
        Route::get('/status/{reference}', [\App\Http\Controllers\P2PController::class, 'getMatchStatus']);
        Route::post('/cancel/{reference}', [\App\Http\Controllers\P2PController::class, 'cancelSearch']);
    });

    Route::get('/withdraw', function () {
        return view('withdraw');
    });
    Route::get('/referal', function () {
        return view('refferal');
    });
    Route::get('/level-management', [Pages::class,'level_management']);

    Route::get('/deposit_withdrawals', [Userdetail::class, "deposit_withdrawal"]);
    Route::get('/logout', function () {
        if (session()->has('userlogin')) {
            session()->forget('userlogin');
        }
        return redirect('/');
    });
    //Api
    Route::get('/get_user_details', [Userdetail::class, "get_user_detail"]);
    // Api Lists App Createion

    //Data api
    Route::post('/user/withdrawal_list', [Userdetail::class, "withdrawal_list"]);
    Route::post('/game/existence', [Gamesetting::class, "game_existence"]);
    Route::post('/game/crash_plane', [Gamesetting::class, "crash_plane"]);
    Route::post('/game/new_game_generated', [Gamesetting::class, "new_game_generated"]);
    Route::post('/game/increamentor', [Gamesetting::class, "increamentor"]);
    Route::post('/game/game_over', [Gamesetting::class, "game_over"]);
    Route::post('/game/add_bet', [Gamesetting::class, "betNow"]);
	// SECURITY: Changed from GET to POST to prevent CSRF and URL parameter exposure
Route::post('/cash_out', [Gamesetting::class, "cashout"]);
    Route::post('/game/currentlybet', [Gamesetting::class, "currentlybet"]);
    Route::post('/game/my_active_bets', [Gamesetting::class, "getMyActiveBets"]);
    Route::post('/game/my_bets_history', [Gamesetting::class, "my_bets_history"]);
    Route::post('/previous_game_bet_list', [Gamesetting::class, "previous_game_bet_list"]);
    Route::get('/payment_gateway_details', [Adminapi::class, "payment_gateway"]);
    Route::post('/insert/withdrawal', [Adminapi::class, "withdrawal_query"]);
    Route::post('/depositNow', [Adminapi::class, "depositNow"]);
    Route::post('/wallet_transfer', [Userdetail::class, "wallet_transfer"]);

    // Xaxino Games
    Route::controller(App\Http\Controllers\PlayController::class)->group(function () {
        Route::get('/play/{alias}/{type?}', 'playGame')->name('game.play');
        Route::post('/play/invest/{alias}/{type?}', 'investGame')->name('game.invest');
        Route::post('/play/end/{alias}/{type?}', 'gameEnd')->name('game.end');
    });
});

// Paystack Webhook (No middleware, has its own signature verification)
Route::post('paystack/webhook', [\App\Http\Controllers\PaystackController::class, 'handleWebhook']);

