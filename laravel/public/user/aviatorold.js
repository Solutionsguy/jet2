$('body').addClass('overflow-hidden');

// ========== BET INTENT HELPER FUNCTION ==========
// Sends betIntent to socket server immediately when a bet is queued
// This triggers AGGRESSIVE mode BEFORE the AJAX call completes
// Works for both manual bets and auto-bets
// @param betAmount - the bet amount
// @param isAutoBet - true if this is an auto-bet (should be treated as first bet placer)
function sendBetIntent(betAmount, isAutoBet = false) {
    try {
        if (typeof getAviatorSocket === 'function') {
            const aviatorSocket = getAviatorSocket();
            if (aviatorSocket && typeof aviatorSocket.isSocketConnected === 'function' && aviatorSocket.isSocketConnected()) {
                const rawSocket = aviatorSocket.getSocket();
                if (rawSocket && rawSocket.connected) {
                    // Get wallet balance from UI for quick validation
                    const walletText = $("#wallet_balance").text();
                    const walletBalance = parseFloat(walletText.replace(/[^0-9.]/g, '')) || 0;
                    
                    rawSocket.emit('betIntent', {
                        odapuId: member_id,
                        odapu: username,
                        username: username,
                        userId: member_id,
                        betAmount: parseFloat(betAmount) || min_bet_amount,
                        walletBalance: walletBalance,
                        timestamp: Date.now(),
                        isAutoBet: isAutoBet,  // Auto-bets are first bet placers (queued before round)
                        priority: isAutoBet ? 'first' : 'normal'  // Mark priority for crash calculation
                    });
                    return true;
                }
            }
        }
    } catch (e) {
        console.log('⚠️ sendBetIntent error:', e.message);
    }
    return false;
}
// ================================================

// ========== BET CANCEL HELPER FUNCTION ==========
// Notifies socket server when a user cancels their bet
// If bet count reaches 0, server switches back to RELAXED mode
function sendBetCancel() {
    try {
        if (typeof getAviatorSocket === 'function') {
            const aviatorSocket = getAviatorSocket();
            if (aviatorSocket && typeof aviatorSocket.isSocketConnected === 'function' && aviatorSocket.isSocketConnected()) {
                const rawSocket = aviatorSocket.getSocket();
                if (rawSocket && rawSocket.connected) {
                    rawSocket.emit('betCancel', {
                        odapuId: member_id,
                        odapu: username,
                        username: username,
                        userId: member_id,
                        timestamp: Date.now()
                    });
                    console.log('🚫 BET CANCEL sent to server');
                    return true;
                }
            }
        }
    } catch (e) {
        console.log('⚠️ sendBetCancel error:', e.message);
    }
    return false;
}
// ================================================

function scrollFunction() {
    $(".list-body").mCustomScrollbar({
        scrollInertia: 50,
        theme: "dark-3"
    });
}
scrollFunction();


document.addEventListener("visibilitychange", function () {
    // console.log(document.visibilityState); // "hidden" or "visible"
    // console.log(document.hidden); // true or false
    const music = document.getElementById("background_Audio");
    if (document.hidden) {
        music.pause();
    } else {
        if (window_blur == 0) {
            window.audioHandler.safePlay(music).catch(e => console.log('Audio play prevented:', e));
        } else {
            music.pause();
        }
        // sound.play()
    }
}, false);

/*-------HINAL (START)-------*/
$(document).on('hidden.bs.modal', '#bet-history', function () {
    $(".bet_record_count").remove();
})
/*---------HINAL (END)-------*/

// Bet Tab Change Functionality START
$(".tabs-navs .nav-item").click(function () {
    $(this).parent().parent().find(".nav-item").removeClass('active');
    $(this).addClass('active');
});

$(".auto-btn").click(function () {
    $(this).parent().parent().find("#bet_type").val(1);
});

$(".bet-btn").click(function () {
    $(this).parent().parent().find("#bet_type").val(0);
});

$(".navigation-switcher .slider").click(function () {
    $(this).parent().find(".slider").removeClass('active');
    $(this).addClass('active');

    const type = $(this).text();
    if (type == 'Auto') {
        $(this).parent().parent().parent().find(".second-row").addClass('show');
    } else {
        $(this).parent().parent().parent().find(".second-row").removeClass('show');
    }
});

$(".cash-out-switcher .form-check .form-check-input").change(function () {
    if (this.checked) {
        $(this).parent().parent().parent().find(".cashout-spinner-wrapper input").attr('disabled', false);
        $(this).parent().parent().parent().parent().parent().find(".navigation").addClass('stop-action');
    } else {
        $(this).parent().parent().parent().find(".cashout-spinner-wrapper input").attr('disabled', true);
        $(this).parent().parent().parent().parent().parent().find(".navigation").removeClass('stop-action');
    }
});

$("#remove_extra_section_btn").click(function () {
    $("#extra_bet_section").hide();
    $("#add_extra_bet_section_btn").show();
});

$("#add_extra_bet_section_btn").click(function () {
    $("#extra_bet_section").show();
    $("#add_extra_bet_section_btn").hide();
});
// Bet Tab Change Functionality END

function bet_amount_incremental(element) {
    var bet_amount = parseFloat($(element).parent().parent().find(".input #bet_amount").val());
    bet_amount++;
    if (bet_amount <= max_bet_amount) {
        $(element).parent().parent().find(".input #bet_amount").val(bet_amount);
    }
}

function bet_amount_decremental(element) {
    var bet_amount = parseFloat($(element).parent().parent().find(".input #bet_amount").val());
    bet_amount--;
    if (bet_amount >= min_bet_amount) {
        $(element).parent().parent().find(".input #bet_amount").val(bet_amount);
    }
}

function select_direct_bet_amount(element) {
    var current_bet_amount = parseFloat($(element).parent().parent().find(".input #bet_amount").val());
    var adding_bet_amount = parseFloat($(element).find(".amt").text()).toFixed(2);
    if ($(element).hasClass('same')) {
        var new_bet_amount = parseFloat(parseFloat(current_bet_amount) + parseFloat(adding_bet_amount)).toFixed(2);
        if (new_bet_amount <= max_bet_amount) {
            $(element).parent().parent().find(".input #bet_amount").val(new_bet_amount);
        }
    } else {
        $(element).parent().find('.bet-opt').removeClass('same');
        $(element).addClass('same');
        $(element).parent().parent().find(".input #bet_amount").val(adding_bet_amount);
    }
}

var current_game_count;
var multiplier_limit = 0;
var stop_position = 0;

$('.loading-game').addClass('show');
// gameLoadingTimer();

$(document).ready(function () {
    let music = document.getElementById("background_Audio");
    music.volume = 0.2;
    if ($("#music").prop("checked") == true) {
        music.loop = true;
        music.load();
    } else {
        music.pause();
    }
    $("#wallet_balance").text(currency_symbol + wallet_balance); // Show Wallet Balance
    $("#header_wallet_balance").text(currency_symbol + wallet_balance); // Show Header Wallet Balance
});

function info_data(intialData) {
    current_game_data = intialData.currentGame;
    current_game_count = intialData.currentGameBetCount;
    show_bet_count(current_game_count);
    update_bet_list(intialData.currentGameBet, '#all_bets .mCSB_container', 1);
}

var main_counter = 0;
var extra_counter = 0;
function cash_out_now(element, section_no, increment = '') {
    
    // ========== CRASH PROTECTION ==========
    // Block ALL cashout attempts after game has crashed
    // This prevents losses from late/lagging cashout requests
    if (window.gameCrashed === true) {
        console.log('❌ BLOCKED: Cashout attempt after crash at', window.crashedAtMultiplier + 'x');
        // Hide cashout buttons immediately
        $("#main_bet_section").find("#cashout_button").hide();
        $("#extra_bet_section").find("#cashout_button").hide();
        return; // Exit function - do not process cashout
    }
    // ======================================

    if (section_no == 0) {
        cashOutSound();
    } else {
        cashOutSoundOtherSection();
    }

    let incrementor;
    if (increment != '') {
        incrementor = increment;
    } else {
        incrementor = $("#auto_increment_number").text().slice(0, -1);
    }

    if (section_no == 0) {
        enableDisable('main_bet_section');
        main_cash_out = 0;
    } else {
        enableDisable('extra_bet_section');
        extra_cash_out = 0;
    }

    if (bet_array.length == 1) {
        bet_array.splice(0, 1); // Remove Perticular Bet
    } else if (bet_array.length == 2 && section_no == 0) {
        if (bet_array[0].section_no == 0) {
            bet_array.splice(0, 1); // Remove Perticular Bet
        } else {
            bet_array.splice(1, 1); // Remove Perticular Bet
        }
    } else if (bet_array.length == 2 && section_no == 1) {
        if (bet_array[0].section_no == 1) {
            bet_array.splice(0, 1); // Remove Perticular Bet
        } else {
            bet_array.splice(1, 1); // Remove Perticular Bet
        }
    }

    let bet_id;
    if (section_no == 0) {
        bet_id = $("#main_bet_id").val();
        var bet_amount = $("#main_bet_section #bet_amount").val();
    } else {
        bet_id = $("#extra_bet_id").val();
        var bet_amount = $("#extra_bet_section #bet_amount").val();
    }
    // let incrementor = $("#auto_increment_number").text().slice(0,-1);
    game_id = current_game_data.id

    // Calculate winnings: bet_amount × multiplier (same formula for all currencies)
    var amt = parseFloat(parseFloat(incrementor) * parseFloat(bet_amount)).toFixed(2);

    $('#all_bets .mCSB_container .bet_id_' + member_id + section_no + '').addClass('active');
    $('#all_bets .mCSB_container .bet_id_' + member_id + section_no + ' .column-3').html('<div class="' + get_multiplier_badge_class(incrementor) + ' custom-badge mx-auto">' + incrementor + 'x</div>');
    $('#all_bets .mCSB_container .bet_id_' + member_id + section_no + ' .column-4').html(amt + currency_symbol);

    if (section_no == 0) {
        let is_main_auto_bet_checked = $("#main_auto_bet").prop('checked');
        if (is_main_auto_bet_checked) {
            $("#main_bet_section").find("#bet_button").hide();
            $("#main_bet_section").find("#cancle_button").show();
            $("#main_bet_section").find("#cancle_button #waiting").show();
            $("#main_bet_section").find("#cashout_button").hide();
            $("#main_bet_section .controls").removeClass('bet-border-yellow');
            $("#main_bet_section .controls").addClass('bet-border-red');
        } else {
            $("#main_bet_section").find("#bet_button").show();
            $("#main_bet_section").find("#cancle_button").hide();
            $("#main_bet_section").find("#cancle_button #waiting").hide();
            $("#main_bet_section").find("#cashout_button").hide();
            $("#main_bet_section .controls").removeClass('bet-border-red');
            $("#main_bet_section .controls").removeClass('bet-border-yellow');
        }

        $("#main_bet_section").find("#cash_out_amount").text('');
        $(".cashout-toaster1 .stop-number").html(incrementor + 'x');
        $(".cashout-toaster1 .out-amount").html(amt + currency_symbol);
        $(".cashout-toaster1").addClass('show');
        firstToastr();
    }

    if (section_no == 1) {
        let is_extra_auto_bet_checked = $("#extra_auto_bet").prop('checked');
        if (is_extra_auto_bet_checked) {
            $("#extra_bet_section").find("#bet_button").hide();
            $("#extra_bet_section").find("#cancle_button").show();
            $("#extra_bet_section").find("#cancle_button #waiting").show();
            $("#extra_bet_section").find("#cashout_button").hide();
            $("#extra_bet_section .controls").removeClass('bet-border-yellow');
            $("#extra_bet_section .controls").addClass('bet-border-red');
        } else {
            $("#extra_bet_section").find("#bet_button").show();
            $("#extra_bet_section").find("#cancle_button").hide();
            $("#extra_bet_section").find("#cancle_button #waiting").hide();
            $("#extra_bet_section").find("#cashout_button").hide();
            $("#extra_bet_section .controls").removeClass('bet-border-red');
            $("#extra_bet_section .controls").removeClass('bet-border-yellow');
        }

        $("#extra_bet_section").find("#cash_out_amount").text('');
        $(".cashout-toaster2 .stop-number").html(incrementor + 'x');
        $(".cashout-toaster2 .out-amount").html(amt + currency_symbol);
        $(".cashout-toaster2").addClass('show');
        secondToastr();
    }

    // toastr.success('You have cashed out! ' + incrementor + 'x You got ' + amt + currency_symbol);

    // SECURITY: Using POST instead of GET to prevent CSRF and URL exposure
    $.ajax({
        url: 'cash_out',
        data: {
            _token: hash_id,  // CSRF token
            game_id: game_id,
            bet_id: bet_id,
            win_multiplier: incrementor,
        },
        type: "POST",
        dataType: "json",
        success: function (result) {
            if (result.isSuccess) {
                if (result.data.wallet_balance != '' && result.data.wallet_balance != NaN && result.data.wallet_balance != 'NaN') {
                    $("#wallet_balance").text(currency_symbol + result.data.wallet_balance);
                    $("#header_wallet_balance").text(currency_symbol + result.data.wallet_balance); // Show Header Wallet Balance
                } else {
                    $("#wallet_balance").text(currency_symbol + '0.00');
                    $("#header_wallet_balance").text(currency_symbol + '0.00'); // Show Header Wallet Balance
                }
                if (section_no == 0) {
                    $("#main_bet_section").find("#bet_button").show();
                    $("#main_bet_section").find("#cancle_button").hide();
                    $("#main_bet_section").find("#cancle_button #waiting").hide();
                    $("#main_bet_section").find("#cashout_button").hide();
                    
                    $("#my_bet_list #my_bet_section_0").addClass('active');
                    $("#my_bet_list #my_bet_section_0 .column-3").html('<div class="' + get_multiplier_badge_class(incrementor) + ' custom-badge mx-auto">' + incrementor + 'x</div>');
                    $("#my_bet_list #my_bet_section_0 .column-4").html(result.data.cash_out_amount + currency_symbol);
                    let is_main_auto_bet_checked = $("#main_auto_bet").prop('checked');

                    $("#my_bet_list #my_bet_section_0").removeAttr('id');


                    if (is_main_auto_bet_checked == false) {
                        // Main Bet Button and text box enable
                        $(".main_bet_amount").prop('disabled', false);
                        $("#main_plus_btn").prop('disabled', false);
                        $("#main_minus_btn").prop('disabled', false);
                        $(".main_amount_btn").prop('disabled', false);
                        $("#main_checkout").prop('disabled', false)
                        if ($("#main_checkout").prop('checked')) {
                            $("#main_incrementor").prop('disabled', false);
                        }
                    }
                    $("#main_auto_bet").prop('disabled', false);
                }
                if (section_no == 1) {
                    $("#extra_bet_section").find("#bet_button").show();
                    $("#extra_bet_section").find("#cancle_button").hide();
                    $("#extra_bet_section").find("#cancle_button #waiting").hide();
                    $("#extra_bet_section").find("#cashout_button").hide();
                    
                    $("#my_bet_list #my_bet_section_1").addClass('active');
                    $("#my_bet_list #my_bet_section_1 .column-3").html('<div class="' + get_multiplier_badge_class(incrementor) + ' custom-badge mx-auto">' + incrementor + 'x</div>');
                    $("#my_bet_list #my_bet_section_1 .column-4").html(result.data.cash_out_amount + currency_symbol);
                    let is_extra_auto_bet_checked = $("#extra_auto_bet").prop('checked');

                    $("#my_bet_list #my_bet_section_1").removeAttr('id');


                    if (is_extra_auto_bet_checked == false) {
                        // Extra Bet Button and text box enable
                        $(".extra_bet_amount").prop('disabled', false);
                        $("#extra_minus_btn").prop('disabled', false);
                        $("#extra_plus_btn").prop('disabled', false);
                        $(".extra_amount_btn").prop('disabled', false);
                        $("#extra_checkout").prop('disabled', false);
                        if ($("#extra_checkout").prop('checked')) {
                            $("#extra_incrementor").prop('disabled', false);
                        }
                    }
                    $("#extra_auto_bet").prop('disabled', false);

                }
            }
        }
    });
}

function crash_plane(inc_no) {
    soundPlay();
    
    // Reset plane glow effect on crash
    if (typeof resetPlaneGlow === 'function') {
        resetPlaneGlow();
    }
    
    // IMPORTANT: Stop all animations before starting crash sequence
    // This prevents overlapping animations
    if (typeof stopPlaneAnimations === 'function') {
        stopPlaneAnimations();
    }
    
    $(".flew_away_section").show();
    $("#auto_increment_number").addClass('text-danger');
    
    // Now start the fly-away animation (stopPlane handles its own interval)
    stopPlane();
    
    // Don't clear canvas here - stopPlane will handle it
    // ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
    $("#running_type").text('rest time');
    update_round_history(inc_no);
    const number_of_bet = $(".round-history-list").find('.custom-badge').length;
    if (number_of_bet > 50) {
        $(".round-history-list").find('.custom-badge:last').remove();
    }

    let is_main_auto_bet_checked = $("#main_auto_bet").prop('checked');
    let is_extra_auto_bet_checked = $("#extra_auto_bet").prop('checked');

    const main_bet_id = $("#main_bet_id").val();
    const extra_bet_id = $("#extra_bet_id").val();

    setTimeout(function () {
        const incrementor = $("#auto_increment_number").text().slice(0, -1);
        if (main_cash_out == 2) {
            $("#main_bet_id").val(main_bet_id);
            const main_inc = main_incrementor;
            if (parseFloat(incrementor) >= parseFloat(main_inc)) {
                cash_out_now('', 0, main_inc);
            }
            $("#main_bet_id").val('');
        }

        if (extra_cash_out == 2) {
            $("#extra_bet_id").val(extra_bet_id);
            const extra_inc = extra_incrementor;
            if (parseFloat(incrementor) >= parseFloat(extra_inc)) {
                cash_out_now('', 1, extra_inc);
            }
            $("#extra_bet_id").val('');
        }
        main_cash_out = 0;
        extra_cash_out = 0;
    }, 1000);

    

    // if (bet_array.length == 2) {
        if (bet_array[0] && bet_array[0].is_bet != undefined) {
            if (bet_array[0].section_no == 0) {
                if (is_main_auto_bet_checked) {
                    $("#main_bet_section").find("#bet_button").hide();
                    $("#main_bet_section").find("#cancle_button").show();
                    $("#main_bet_section").find("#cancle_button #waiting").show();
                    $("#main_bet_section").find("#cashout_button").hide();
                    $("#main_bet_section .controls").removeClass('bet-border-yellow');
                    $("#main_bet_section .controls").addClass('bet-border-red');
                } else {
                    $("#main_bet_section").find("#bet_button").show();
                    $("#main_bet_section").find("#cancle_button").hide();
                    $("#main_bet_section").find("#cancle_button #waiting").hide();
                    $("#main_bet_section").find("#cashout_button").hide();
                    $("#main_bet_section .controls").removeClass('bet-border-red');
                    $("#main_bet_section .controls").removeClass('bet-border-yellow');

                    // Main Bet
                    $(".main_bet_amount").prop('disabled', false);
                    $("#main_plus_btn").prop('disabled', false);
                    $("#main_minus_btn").prop('disabled', false);
                    $(".main_amount_btn").prop('disabled', false);
                    $("#main_checkout").prop('disabled', false);
                    if ($("#main_checkout").prop('checked')) {
                        $("#main_incrementor").prop('disabled', false);
                    }
                }

                $("#main_bet_id").val('');
                $("#main_bet_section").find("#cash_out_amount").text('');


                $("#main_auto_bet").prop('disabled', false);
            } else if (bet_array[0].section_no == 1) {
                if (is_extra_auto_bet_checked) {
                    $("#extra_bet_section").find("#bet_button").hide();
                    $("#extra_bet_section").find("#cancle_button").show();
                    $("#extra_bet_section").find("#cancle_button #waiting").show();
                    $("#extra_bet_section").find("#cashout_button").hide();
                    $("#extra_bet_section .controls").removeClass('bet-border-yellow');
                    $("#extra_bet_section .controls").addClass('bet-border-red');
                } else {
                    $("#extra_bet_section").find("#bet_button").show();
                    $("#extra_bet_section").find("#cancle_button").hide();
                    $("#extra_bet_section").find("#cancle_button #waiting").hide();
                    $("#extra_bet_section").find("#cashout_button").hide();
                    $("#extra_bet_section .controls").removeClass('bet-border-red');
                    $("#extra_bet_section .controls").removeClass('bet-border-yellow');

                    // Extra Bet
                    $(".extra_bet_amount").prop('disabled', false);
                    $("#extra_minus_btn").prop('disabled', false);
                    $("#extra_plus_btn").prop('disabled', false);
                    $(".extra_amount_btn").prop('disabled', false);
                    $("#extra_checkout").prop('disabled', false);
                    if ($("#extra_checkout").prop('checked')) {
                        $("#extra_incrementor").prop('disabled', false);
                    }
                }

                $("#extra_bet_id").val('');
                $("#extra_bet_section").find("#cash_out_amount").text('');


                $("#extra_auto_bet").prop('disabled', false);
            }
        }
        if (bet_array[1] && bet_array[1].is_bet != undefined) {
            if (bet_array[1].section_no == 0) {
                if (is_main_auto_bet_checked) {
                    $("#main_bet_section").find("#bet_button").hide();
                    $("#main_bet_section").find("#cancle_button").show();
                    $("#main_bet_section").find("#cancle_button #waiting").show();
                    $("#main_bet_section").find("#cashout_button").hide();
                    $("#main_bet_section .controls").removeClass('bet-border-yellow');
                    $("#main_bet_section .controls").addClass('bet-border-red');
                } else {
                    $("#main_bet_section").find("#bet_button").show();
                    $("#main_bet_section").find("#cancle_button").hide();
                    $("#main_bet_section").find("#cancle_button #waiting").hide();
                    $("#main_bet_section").find("#cashout_button").hide();
                    $("#main_bet_section .controls").removeClass('bet-border-red');
                    $("#main_bet_section .controls").removeClass('bet-border-yellow');

                    // Main Bet
                    $(".main_bet_amount").prop('disabled', false);
                    $("#main_plus_btn").prop('disabled', false);
                    $("#main_minus_btn").prop('disabled', false);
                    $(".main_amount_btn").prop('disabled', false);
                    $("#main_checkout").prop('disabled', false);
                    if ($("#main_checkout").prop('checked')) {
                        $("#main_incrementor").prop('disabled', false);
                    }
                }

                $("#main_bet_id").val('');
                $("#main_bet_section").find("#cash_out_amount").text('');


                $("#main_auto_bet").prop('disabled', false);
            } else if (bet_array[1].section_no == 1) {
                if (is_extra_auto_bet_checked) {
                    $("#extra_bet_section").find("#bet_button").hide();
                    $("#extra_bet_section").find("#cancle_button").show();
                    $("#extra_bet_section").find("#cancle_button #waiting").show();
                    $("#extra_bet_section").find("#cashout_button").hide();
                    $("#extra_bet_section .controls").removeClass('bet-border-yellow');
                    $("#extra_bet_section .controls").addClass('bet-border-red');
                } else {
                    $("#extra_bet_section").find("#bet_button").show();
                    $("#extra_bet_section").find("#cancle_button").hide();
                    $("#extra_bet_section").find("#cancle_button #waiting").hide();
                    $("#extra_bet_section").find("#cashout_button").hide();
                    $("#extra_bet_section .controls").removeClass('bet-border-red');
                    $("#extra_bet_section .controls").removeClass('bet-border-yellow');

                    // Extra Bet
                    $(".extra_bet_amount").prop('disabled', false);
                    $("#extra_minus_btn").prop('disabled', false);
                    $("#extra_plus_btn").prop('disabled', false);
                    $(".extra_amount_btn").prop('disabled', false);
                    $("#extra_checkout").prop('disabled', false);
                    if ($("#extra_checkout").prop('checked')) {
                        $("#extra_incrementor").prop('disabled', false);
                    }
                }

                $("#extra_bet_id").val('');
                $("#extra_bet_section").find("#cash_out_amount").text('');


                $("#extra_auto_bet").prop('disabled', false);
            }
        }
    // }
}


function new_game_generated() {
    is_game_generated = 1;
    $('#my_bet_list .mCSB_container .list-items').removeAttr('id');
    $(".game-centeral-loading").show();
    
    // Reset plane glow effect for new round
    if (typeof resetPlaneGlow === 'function') {
        resetPlaneGlow();
    }

    $("#main_bet_section").find("#cancle_button #waiting").hide();
    $("#extra_bet_section").find("#cancle_button #waiting").hide();

    if (bet_array.length == 1) {
        if (bet_array[0].section_no == 0) {
            enableDisable('main_bet_section');
        }
        if (bet_array[0].section_no == 1) {
            enableDisable('extra_bet_section');
        }
    }
    if (bet_array.length == 2) {
        enableDisable('main_bet_section');
        enableDisable('extra_bet_section');
    }

    $(".load-txt").hide();
    $('body').removeClass('overflow-hidden');
    document.getElementById('auto_increment_number').innerText = '1.00x';
    // $('.loading-game').addClass('show');
    //khushbu
    $('.loading-game').addClass('show');
    // setTimeout(hide_loading_game(), 10000);
    $(".flew_away_section").hide();
    $("#auto_increment_number").removeClass('text-danger');
    // NOTE: Don't clear bets here - they are managed by socket sync now
    // $("#all_bets .mCSB_container").html('');
    $("#running_type").text('bet time');
    $("#auto_increment_number_div").hide();
    //khushbu
    current_game_count = 0;

    let is_main_auto_bet_checked = $("#main_auto_bet").prop('checked');
    if (is_main_auto_bet_checked) {
        if (bet_array.length != 2 && (bet_array.length == 0 || (bet_array.length == 1 && bet_array[0].section_no != 0))) {
            var bet_type = $("#main_bet_now").parent().parent().parent().find(".navigation #bet_type").val(); // 0 - Normal, 1 - Auto
            let bet_amount = $("#main_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val();

            if (bet_amount < min_bet_amount || bet_amount == '' || bet_amount == NaN) {
                bet_amount = parseFloat(min_bet_amount).toFixed(2);
            } else if (bet_amount > max_bet_amount) {
                bet_amount = parseFloat(max_bet_amount).toFixed(2);
            } else {
                bet_amount = parseFloat(bet_amount).toFixed(2);
            }

            $("#main_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val(bet_amount);

            if (bet_amount >= min_bet_amount && bet_amount <= max_bet_amount) {
                bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: 0 });
                
                // NOTE: betIntent is now sent ONLY from the auto-bet toggle change handler
                // and from bet_now() when user clicks BET button.
                // Sending here causes "invalid game status" errors because new_game_generated()
                // is called during the flying phase, not waiting phase.
            }
        }

    }

    let is_extra_auto_bet_checked = $("#extra_auto_bet").prop('checked');
    if (is_extra_auto_bet_checked) {
        if (bet_array.length != 2 && (bet_array.length == 0 || (bet_array.length == 1 && bet_array[0].section_no != 1))) {
            var bet_type = $("#extra_bet_now").parent().parent().parent().find(".navigation #bet_type").val(); // 0 - Normal, 1 - Auto
            let bet_amount = $("#extra_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val();

            if (bet_amount < min_bet_amount || bet_amount == '' || bet_amount == NaN) {
                bet_amount = parseFloat(min_bet_amount).toFixed(2);
            } else if (bet_amount > max_bet_amount) {
                bet_amount = parseFloat(max_bet_amount).toFixed(2);
            } else {
                bet_amount = parseFloat(bet_amount).toFixed(2);
            }

            $("#extra_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val(bet_amount);

            if (bet_amount >= min_bet_amount && bet_amount <= max_bet_amount) {
                bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: 1 });
                
                // NOTE: betIntent is now sent ONLY from the auto-bet toggle change handler
                // and from bet_now() when user clicks BET button.
                // Sending here causes "invalid game status" errors because new_game_generated()
                // is called during the flying phase, not waiting phase.
            }
        }

    }

}

function lets_fly_one() {
    is_game_generated = 0;
    $(".stage-board").addClass('blink_section');
    $(".bet-controls").addClass('blink_section');
}

function lets_fly() {
    $(".stage-board").removeClass('blink_section');
    $(".bet-controls").removeClass('blink_section');
    stage_time_out = 0;
    if (bet_array.length == 1 && bet_array[0] && bet_array[0].section_no == 0) {
        // console.log(bet_array);
        enableDisable('main_bet_section');
        $("#main_bet_section").find("#bet_button").hide();
        $("#main_bet_section").find("#cancle_button").hide();
        $("#main_bet_section").find("#cancle_button #waiting").hide();
        $("#main_bet_section").find("#cashout_button").show();
        $("#main_bet_section .controls").removeClass('bet-border-red');
        $("#main_bet_section .controls").addClass('bet-border-yellow');
        $("#main_auto_bet").prop('disabled', true);
        $("#main_checkout").prop('disabled', true);
        $("#main_incrementor").prop('disabled', true);
    }

    if (bet_array.length == 1 && bet_array[0] && bet_array[0].section_no == 1) {
        enableDisable('extra_bet_section');
        $("#extra_bet_section").find("#bet_button").hide();
        $("#extra_bet_section").find("#cancle_button").hide();
        $("#extra_bet_section").find("#cancle_button #waiting").hide();
        $("#extra_bet_section").find("#cashout_button").show();
        $("#extra_bet_section .controls").removeClass('bet-border-red');
        $("#extra_bet_section .controls").addClass('bet-border-yellow');
        $("#extra_auto_bet").prop('disabled', true);
        $("#extra_checkout").prop('disabled', true);
        $("#extra_incrementor").prop('disabled', true);
    }

    if (bet_array.length == 2) {

        if (bet_array[0] && bet_array[0].section_no == 0) {
            enableDisable('main_bet_section');
            $("#main_bet_section").find("#bet_button").hide();
            $("#main_bet_section").find("#cancle_button").hide();
            $("#main_bet_section").find("#cancle_button #waiting").hide();
            $("#main_bet_section").find("#cashout_button").show();
            $("#main_bet_section .controls").removeClass('bet-border-red');
            $("#main_bet_section .controls").addClass('bet-border-yellow');
            $("#main_auto_bet").prop('disabled', true);
            $("#main_checkout").prop('disabled', true);
            $("#main_incrementor").prop('disabled', true);
        }

        if (bet_array[0] && bet_array[0].section_no == 1) {
            enableDisable('extra_bet_section');
            $("#extra_bet_section").find("#bet_button").hide();
            $("#extra_bet_section").find("#cancle_button").hide();
            $("#extra_bet_section").find("#cancle_button #waiting").hide();
            $("#extra_bet_section").find("#cashout_button").show();
            $("#extra_bet_section .controls").removeClass('bet-border-red');
            $("#extra_bet_section .controls").addClass('bet-border-yellow');
            $("#extra_auto_bet").prop('disabled', true);
            $("#extra_checkout").prop('disabled', true);
            $("#extra_incrementor").prop('disabled', true);
        }

        if (bet_array[1] && bet_array[1].section_no == 0) {
            enableDisable('main_bet_section');
            $("#main_bet_section").find("#bet_button").hide();
            $("#main_bet_section").find("#cancle_button").hide();
            $("#main_bet_section").find("#cancle_button #waiting").hide();
            $("#main_bet_section").find("#cashout_button").show();
            $("#main_bet_section .controls").removeClass('bet-border-red');
            $("#main_bet_section .controls").addClass('bet-border-yellow');
            $("#main_auto_bet").prop('disabled', true);
            $("#main_checkout").prop('disabled', true);
            $("#main_incrementor").prop('disabled', true);
        }

        if (bet_array[1] && bet_array[1].section_no == 1) {
            enableDisable('extra_bet_section');
            $("#extra_bet_section").find("#bet_button").hide();
            $("#extra_bet_section").find("#cancle_button").hide();
            $("#extra_bet_section").find("#cancle_button #waiting").hide();
            $("#extra_bet_section").find("#cashout_button").show();
            $("#extra_bet_section .controls").removeClass('bet-border-red');
            $("#extra_bet_section .controls").addClass('bet-border-yellow');
            $("#extra_auto_bet").prop('disabled', true);
            $("#extra_checkout").prop('disabled', true);
            $("#extra_incrementor").prop('disabled', true);
        }
    }

    $(".load-txt").hide();
    $('body').removeClass('overflow-hidden');
    // Reset auto cash-out counters for the new round
    main_counter = 0;
    extra_counter = 0;
    $('.loading-game').removeClass('show');
    $("#auto_increment_number_div").show();
    setVariable(1);
    flyPlaneSound();
}

function incrementor(inc_no) {
    $('.loading-game').removeClass('show');
    $("#auto_increment_number_div").show();
    $("#running_type").text('cash out time');
    document.getElementById('auto_increment_number').innerText = inc_no + '' + 'x';
    
    // Update plane glow effect based on current multiplier
    if (typeof updatePlaneGlow === 'function') {
        updatePlaneGlow(inc_no, true);
    }

    if (bet_array.length > 0) {

        let main_isChecked = $('#main_checkout').prop('checked');
        let extra_isChecked = $("#extra_checkout").prop('checked');
        let incrementor;

        for (let i = 0; i < bet_array.length; i++) {
            if (bet_array[i].section_no == 0) {
                if (bet_array[i].is_bet == 1) {
                    if (main_isChecked == true) {
                        incrementor = $('#main_incrementor').val();
                        main_incrementor = incrementor;
                        if (parseFloat(inc_no) >= parseFloat(incrementor)) {
                            if (main_counter == 0) {
                                cash_out_now('', 0, incrementor);
                                main_counter++;
                                main_cash_out = 1;
                            }
                        } else {
                            main_cash_out = 2;
                        }
                    }
                }
            } else if (bet_array[i].section_no == 1) {
                if (bet_array[i].is_bet == 1) {
                    if (extra_isChecked == true) {
                        incrementor = $('#extra_incrementor').val();
                        extra_incrementor = incrementor;
                        if (parseFloat(inc_no) >= parseFloat(incrementor)) {
                            if (extra_counter == 0) {
                                cash_out_now('', 1, incrementor);
                                extra_counter++;
                                extra_cash_out = 1;
                            }
                        } else {
                            extra_cash_out = 2;
                        }
                    }
                }
            }
        }

    }
    if (bet_array.length > 0) {
        cash_out_multiplier(inc_no);
    }

}

function cash_out_bet(cashOutData) {
    var betRow = $('#all_bets .mCSB_container .' + cashOutData.hash_id);
    
    // Remove any lost styling first (in case game crashed before update)
    betRow.removeClass('bet-lost');
    
    // Add active state and cashout highlight animation
    betRow.addClass('active cashed-out-highlight');
    
    // Get the correct color class for this multiplier
    var colorClass = get_multiplier_badge_class(cashOutData.incrementor);
    console.log('💰 Cashout badge color:', cashOutData.incrementor + 'x', '→', colorClass);
    
    // Update multiplier badge - remove any existing color classes first, then add correct one
    betRow.find('.column-3').html('<div class="custom-badge mx-auto ' + colorClass + '">' + cashOutData.incrementor + 'x</div>');
    
    // Use the amount directly - no currency conversion needed
    var amt = cashOutData.amount || cashOutData.inr_amount || 0;
    betRow.find('.column-4').html(amt + currency_symbol);
    
    // Remove highlight animation class after it completes (keep active state)
    setTimeout(function() {
        betRow.removeClass('cashed-out-highlight');
    }, 2000);
}

function update_bet_list(bets, target, appendType = '') {
    //khushbu 
    // show_bet_count(bets.length);
    if (appendType == 1) {
        // NOTE: Only clear if not using socket sync (socket handles bet display now)
        if (typeof window.currentRoundBets === 'undefined' || window.currentRoundBets.length === 0) {
            $("#all_bets .mCSB_container").html('');
        }
    }
    if (appendType == 2) {
        $('#prev_bets .mCSB_container').html('');
    }
    var html = '';
    for (i = 0; i < bets.length; i++) {
        var isActive = bets[i].cashout_multiplier > 0 ? "active" : "";
        if (parseFloat(bets[i].cashout_multiplier) <= 2) {
            var badgeColor = 'bg3';
        } else if (parseFloat(bets[i].cashout_multiplier) < 10) {
            var badgeColor = 'bg1';
        } else {
            var badgeColor = 'bg2';
        }
        if (parseFloat(bets[i].cashout_multiplier) > 0) {
            var cashOut = Math.round(bets[i].cashout_multiplier*bets[i].amount) + currency_symbol;
            var multiplication = '<div class="' + badgeColor + ' custom-badge mx-auto">' + bets[i].cashout_multiplier + 'x</div>';
        } else {
            var cashOut = '-';
            var multiplication = '-';
        }
        if (bets[i].class_name != undefined && bets[i].class_name != 'undefined') {
            var sectionNo = 'bet_id_' + '' + bets[i].class_name;
        } else {
            var sectionNo = '';
        }
        html += '<div class="list-items ' + isActive + ' ' + sectionNo + ' ' + '">' +
            '<div class="column-1 users"> <img src="' + bets[i].image + '" class="avatar me-1"> ' + bets[i].userid + ' </div>' +
            '<div class="column-2"> <button class="btn btn-transparent previous-history d-flex align-items-center mx-auto"> ' + bets[i].amount + currency_symbol + ' </button> </div>' +
            '<div class="column-3"> ' + multiplication + ' </div>' +
            '<div class="column-4"> ' + cashOut + ' </div>' +
            '</div>';
    }
    $(target).html(html);
}

function update_my_new_bet(bet_amount, section_no, target) {
    var html = '';
    html += '<div class="list-items" id="my_bet_section_' + section_no + '">' +
        '<div class="column-1 users fw-normal"> ' + get_current_hour_minute() + ' </div>' +
        '<div class="column-2"> <button class="btn btn-transparent previous-history d-flex align-items-center mx-auto fw-normal">' + parseFloat(bet_amount).toFixed(2) + '' + currency_symbol + '</button> </div>' +
        '<div class="column-3"> - </div>' +
        '<div class="column-4 fw-normal"> - </div>' +
        '</div>';
    $(target).prepend(html);
}

function get_multiplier_badge_class(multiplier) {
    if (parseFloat(multiplier) <= 2) {
        return 'bg3';
    } else if (parseFloat(multiplier) < 10) {
        return 'bg1';
    } else {
        return 'bg2';
    }
}

function previous_hand(val) {
    if (val == 1) {
        $("#current_hand_btn").addClass('hide');
        $("#previous_hand_btn").removeClass('hide');
        $("#all_bets").addClass('hide');
        $("#prev_bets").removeClass('hide');
        $("#prev_win_multi").removeClass('hide');
        //khushbu
        prevoius_game_bets(current_game_data.id);
    } else {
        $("#current_hand_btn").removeClass('hide');
        $("#previous_hand_btn").addClass('hide');
        $("#all_bets").removeClass('hide');
        $("#prev_bets").addClass('hide');
        $("#prev_win_multi").addClass('hide');
        //khushbu
        show_bet_count($('#all_bets .mCSB_container .list-items').length);
    }
};

function prevoius_game_bets(game_id) {
    $.ajax({
        url: 'previous_game_bet_list',
        data: {
            game_id: game_id,
        },
        type: "POST",
        dataType: "json",
        success: function (result) {
            if (result.isSuccess && Object.keys(result.data).length > 0) {
                var betList = result.data.bet_list;
                var betCount = result.data.bet_counts;
                var winMulti = result.data.win_multi;
                update_bet_list(betList, '#prev_bets .mCSB_container', 2);
                //khushbu
                show_bet_count(betCount);
                $("#prev_win_multi").addClass(get_multiplier_badge_class(winMulti)).text(parseFloat(winMulti).toFixed(2) + 'x');
            } else {
                $("#prev_win_multi").addClass('bg1');
            }
        }
    });
}

function cash_out_multiplier(inc_no) {
    if (bet_array.length == 1 && bet_array[0].section_no == 0 && bet_array[0].is_bet != undefined) {
        $("#main_bet_section").find("#cash_out_amount").text(parseFloat(bet_array[0].bet_amount * inc_no).toFixed(2) + '' + currency_symbol);
    }

    if (bet_array.length == 1 && bet_array[0].section_no == 1 && bet_array[0].is_bet != undefined) {
        $("#extra_bet_section").find("#cash_out_amount").text(parseFloat(bet_array[0].bet_amount * inc_no).toFixed(2) + '' + currency_symbol);
    }

    if (bet_array.length == 2) {
        $.map(bet_array, function (item, index) {
            if (item.section_no == 0 && item.is_bet != undefined) {
                $("#main_bet_section").find("#cash_out_amount").text(parseFloat(item.bet_amount * inc_no).toFixed(2) + '' + currency_symbol);
            }
            if (item.section_no == 1 && item.is_bet != undefined) {
                $("#extra_bet_section").find("#cash_out_amount").text(parseFloat(item.bet_amount * inc_no).toFixed(2) + '' + currency_symbol);
            }
        });
    }
}

function show_bet_count(count) {
    $("#total_bets").text(count);
}

function bet_now(element, section_no) {
    if (stage_time_out == 1) {
        if (section_no == 0) {
            enableDisable('main_bet_section');
        } else {
            enableDisable('extra_bet_section');
        }
        $(".error-toaster2").addClass('show');
        errorToastrStageTimeOut();
    } else {
        // ========== INSTANT BET INTENT ==========
        // Send betIntent to socket server IMMEDIATELY when user clicks bet
        // This triggers AGGRESSIVE mode BEFORE the AJAX call completes
        let bet_amount_for_intent = $(element).parent().parent().find(".bet-block .spinner #bet_amount").val();
        if (sendBetIntent(bet_amount_for_intent)) {
            console.log('⚡ BET INTENT sent - AGGRESSIVE mode will activate immediately');
        }
        // ========================================
        
        var bet_type = $(element).parent().parent().parent().find(".navigation #bet_type").val(); // 0 - Normal, 1 - Auto
        // var bet_amount = parseFloat($(element).parent().parent().find(".bet-block .spinner #bet_amount").val());
        let bet_amount = $(element).parent().parent().find(".bet-block .spinner #bet_amount").val();

        if (section_no == 0) {
            $("#main_bet_section .controls").addClass('bet-border-red');
        } else if (section_no == 1) {
            $("#extra_bet_section .controls").addClass('bet-border-red');
        }

        if (bet_amount < min_bet_amount || bet_amount == '' || bet_amount == NaN) {
            bet_amount = parseFloat(min_bet_amount).toFixed(2);
        } else if (bet_amount > max_bet_amount) {
            bet_amount = parseFloat(max_bet_amount).toFixed(2);
        } else {
            bet_amount = parseFloat(bet_amount).toFixed(2);
        }

        $(element).parent().parent().find(".bet-block .spinner #bet_amount").val(bet_amount);

        if (bet_amount >= min_bet_amount && bet_amount <= max_bet_amount) {
            $(element).parent().parent().find("#bet_button").hide();
            $(element).parent().parent().find("#cancle_button").show();
            $(element).parent().parent().find("#cancle_button #waiting").show();

            if (is_game_generated == 1) {
                setTimeout(() => {
                    $(element).parent().parent().find("#cancle_button #waiting").hide();
                }, 500);
            }
            
            bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
            
            // Save pending bets to localStorage
            if (typeof savePendingBets === 'function') {
                savePendingBets();
            }
        }
    }
}

function cancle_now(element, section_no) {
    if (stage_time_out == 1) {
        $(".error-toaster2").addClass('show');
        errorToastrStageTimeOut();
    } else {
        if (section_no == 0) {
            $('#main_auto_bet').prop('checked', false);
            $("#main_bet_section .controls").removeClass('bet-border-red');
        } else if (section_no == 1) {
            $('#extra_auto_bet').prop('checked', false);
            $("#extra_bet_section .controls").removeClass('bet-border-red');
        }

        if (bet_array.length == 1) {
            bet_array = [];
        } 
        if (bet_array.length == 2 && section_no == 0) {
            if (bet_array[0].section_no == 0) {
                bet_array.splice(0, 1); // Remove Perticular Bet
            } 
            if (bet_array[0].section_no == 1) {
                bet_array.splice(1, 1); // Remove Perticular Bet
            }
        }

        // Save pending bets to localStorage (or clear if empty)
        if (typeof savePendingBets === 'function') {
            savePendingBets();
        }
        
        // ========== NOTIFY SERVER OF BET CANCELLATION ==========
        // If no more bets, server will switch back to RELAXED mode
        sendBetCancel();
        // =======================================================

        // delete bet_array[section_no];
        $(element).parent().parent().find("#bet_button").show();
        $(element).parent().parent().find("#cancle_button").hide();
        $(element).parent().parent().find("#cancle_button #waiting").hide();
    }
}

function place_bet_now() {
    for(let i=0;i < bet_array.length; i++){
        bet_array[i].game_id = current_game_data.id;
    }

    $.ajax({
        url: '/game/add_bet',
        data: {
            _token: hash_id,
            all_bets: bet_array
        },
        type: "POST",
        dataType: "json",
        success: function (result) {
            if (result.isSuccess) {
                // Clear pending bets from localStorage since they've been placed
                if (typeof clearPendingBetsStorage === 'function') {
                    clearPendingBetsStorage();
                }

                if (result.data.wallet_balance != '' && result.data.wallet_balance != NaN && result.data.wallet_balance != 'NaN') {
                    $("#wallet_balance").text(currency_symbol + result.data.wallet_balance);
                    $("#header_wallet_balance").text(currency_symbol + result.data.wallet_balance); // Show Header Wallet Balance
                } else {
                    $("#wallet_balance").text(currency_symbol + '0.00');
                    $("#header_wallet_balance").text(currency_symbol + '0.00'); // Show Header Wallet Balance
                }
                
                // SOCKET: Broadcast bet to all clients
                if (typeof getAviatorSocket === 'function') {
                    const socket = getAviatorSocket();
                    if (socket && socket.isSocketConnected()) {
                        for (let i = 0; i < bet_array.length; i++) {
                            const betId = result.data.return_bets && result.data.return_bets[i] ? 
                                          result.data.return_bets[i].bet_id : Date.now() + '_' + i;
                            // Check if auto cash-out is enabled for this bet section
                            let autoCashOutAt = null;
                            if (bet_array[i].section_no == 0) {
                                // Main section - check if auto cash-out checkbox is checked
                                if ($('#main_checkout').prop('checked')) {
                                    autoCashOutAt = parseFloat($('#main_incrementor').val()) || null;
                                }
                            } else if (bet_array[i].section_no == 1) {
                                // Extra section - check if auto cash-out checkbox is checked
                                if ($('#extra_checkout').prop('checked')) {
                                    autoCashOutAt = parseFloat($('#extra_incrementor').val()) || null;
                                }
                            }
                            
                            // DEBUG: Log auto cash-out settings before sending
                            console.log('🔍 DEBUG AUTO-CASHOUT:');
                            console.log('   section_no:', bet_array[i].section_no);
                            console.log('   main_checkout checked:', $('#main_checkout').prop('checked'));
                            console.log('   main_incrementor value:', $('#main_incrementor').val());
                            console.log('   extra_checkout checked:', $('#extra_checkout').prop('checked'));
                            console.log('   extra_incrementor value:', $('#extra_incrementor').val());
                            console.log('   FINAL autoCashOutAt:', autoCashOutAt);
                            
                            socket.placeBet({
                                betId: betId,
                                odapuId: typeof user_id !== 'undefined' ? user_id : null,
                                odapu: typeof username !== 'undefined' ? username : 'Guest',
                                username: typeof username !== 'undefined' ? username : 'Guest',
                                amount: bet_array[i].bet_amount,
                                avatar: typeof user_avatar !== 'undefined' ? user_avatar : null,
                                sectionNo: bet_array[i].section_no,
                                // SERVER-SIDE AUTO CASH-OUT: Send target multiplier to server
                                autoCashOutAt: autoCashOutAt
                            });
                            console.log('📡 Bet broadcast via socket:', bet_array[i].bet_amount, autoCashOutAt ? '(auto cash-out at ' + autoCashOutAt + 'x)' : '(NO auto cash-out)');
                        }
                    }
                }

                if (bet_array.length == 1) {
                    update_my_new_bet(bet_array[0].bet_amount, bet_array[0].section_no, '#my_bet_list .mCSB_container');
                } else if (bet_array.length == 2) {
                    if (bet_array[0] != undefined) {
                        update_my_new_bet(bet_array[0].bet_amount, bet_array[0].section_no, '#my_bet_list .mCSB_container');
                    }
                    if (bet_array[1] != undefined) {
                        update_my_new_bet(bet_array[1].bet_amount, bet_array[1].section_no, '#my_bet_list .mCSB_container');
                    }
                }

                if (bet_array.length == 1 && bet_array[0].section_no == 0) {
                    bet_array[0].is_bet = 1;
                    enableDisable('main_bet_section');
                    $("#main_bet_id").val(result.data.return_bets[0].bet_id);
                }

                if (bet_array.length == 1 && bet_array[0].section_no == 1) {
                    bet_array[0].is_bet = 1;
                    enableDisable('extra_bet_section');
                    $("#extra_bet_id").val(result.data.return_bets[0].bet_id);
                }

                if (bet_array.length == 2) {

                    if (bet_array[0].section_no == 0) {
                        $("#main_bet_id").val(result.data.return_bets[0].bet_id);
                        $("#extra_bet_id").val(result.data.return_bets[1].bet_id);
                    bet_array[0].is_bet = 1;
                    }

                    if (bet_array[0].section_no == 1) {
                        $("#main_bet_id").val(result.data.return_bets[1].bet_id);
                        $("#extra_bet_id").val(result.data.return_bets[0].bet_id);
                    bet_array[0].is_bet = 1;
                    }
                    if (bet_array[1].section_no == 0) {
                        $("#main_bet_id").val(result.data.return_bets[0].bet_id);
                        $("#extra_bet_id").val(result.data.return_bets[1].bet_id);
                    bet_array[1].is_bet = 1;
                    }

                    if (bet_array[1].section_no == 1) {
                        $("#main_bet_id").val(result.data.return_bets[1].bet_id);
                        $("#extra_bet_id").val(result.data.return_bets[0].bet_id);
                    bet_array[1].is_bet = 1;
                    }
                }
            } else {
                $(".error-toaster1 .msg").html(result.message);
                $(".error-toaster1").addClass('show');
                errorToastr();

                $("#main_bet_section").find("#bet_button").show();
                $("#main_bet_section").find("#cancle_button").hide();
                $("#main_bet_section").find("#cancle_button #waiting").hide();
                $("#main_bet_section").find("#cashout_button").hide();
                $("#main_bet_section .controls").removeClass('bet-border-red');
                $("#main_bet_section .controls").removeClass('bet-border-yellow');
                $("#main_bet_section .controls .navigation").removeClass('stop-action');

                $("#extra_bet_section").find("#bet_button").show();
                $("#extra_bet_section").find("#cancle_button").hide();
                $("#extra_bet_section").find("#cancle_button #waiting").hide();
                $("#extra_bet_section").find("#cashout_button").hide();
                $("#extra_bet_section .controls").removeClass('bet-border-red');
                $("#extra_bet_section .controls").removeClass('bet-border-yellow');
                $("#extra_bet_section .controls .navigation").removeClass('stop-action');

                // Main Bet
                $(".main_bet_amount").prop('disabled', false);
                $("#main_plus_btn").prop('disabled', false);
                $("#main_minus_btn").prop('disabled', false);
                $(".main_amount_btn").prop('disabled', false);
                $("#main_checkout").prop('disabled', false)
                if ($("#main_checkout").prop('checked')) {
                    $("#main_incrementor").prop('disabled', false);
                }
                $('#main_auto_bet').prop('checked', false);

                // Extra Bet
                $(".extra_bet_amount").prop('disabled', false);
                $("#extra_minus_btn").prop('disabled', false);
                $("#extra_plus_btn").prop('disabled', false);
                $(".extra_amount_btn").prop('disabled', false);
                $("#extra_checkout").prop('disabled', false);
                if ($("#extra_checkout").prop('checked')) {
                    $("#extra_incrementor").prop('disabled', false);
                }
                $('#extra_auto_bet').prop('checked', false);

                bet_array = [];
            }
        }

    });
}

function firstToastr() {
    let first_no = 1;
    var success_toast1 = setInterval(function () {
        if (first_no < 3) {
            first_no++;
        } else {
            $(".cashout-toaster1").removeClass('show');
            clearInterval(success_toast1);
        }
    }, 1000); // for every second
}

function secondToastr() {
    let second_no = 1;
    var success_toast2 = setInterval(function () {
        if (second_no < 3) {
            second_no++;
        } else {
            $(".cashout-toaster2").removeClass('show');
            clearInterval(success_toast2);
        }
    }, 1000); // for every second
}

function errorToastr() {
    let error_no = 1;
    var error_toast1 = setInterval(function () {
        if (error_no < 3) {
            error_no++;
        } else {
            $(".error-toaster1").removeClass('show');
            clearInterval(error_toast1);
        }
    }, 1000); // for every second
}

function errorToastrStageTimeOut() {
    let stage_error_no = 1;
    var error_toast_stage_time_out = setInterval(function () {
        if (stage_error_no < 3) {
            stage_error_no++;
        } else {
            $(".error-toaster2").removeClass('show');
            clearInterval(error_toast_stage_time_out);
        }
    }, 1000); // for every second
}

function get_current_hour_minute() {
    var date = new Date;
    var hour = date.getHours();
    var minutes = date.getMinutes();

    if (hour.toString().length > 1) {
        var retHour = hour;
    } else {
        var retHour = '0' + hour;
    }

    if (minutes.toString().length > 1) {
        var retMinute = minutes;
    } else {
        var retMinute = '0' + minutes;
    }

    return retHour + ':' + retMinute;
}

function update_round_history(inc_no) {
    var html = '<div class="' + get_multiplier_badge_class(inc_no) + ' custom-badge">' + parseFloat(inc_no).toFixed(2) + 'x</div>'
    $(".payouts-wrapper .payouts-block").prepend(html);
    $(".button-block .history-dropdown .round-history-list").prepend(html);
}

/*-------HINAL (START)-------*/

function loadData() {
    const numItems = $('.bet_record_count').length;
    $.ajax({
        url: '/member_bet',
        type: 'post',
        data: {
            'offset': numItems,
        },
        success: function (result) {
            const length = result.data.length;
            if (length > 0) {
                for (let i = 0; i < length; i++) {
                    if (parseFloat(result.data[i].multiplication) > 0) {
                        var multiplier = `<div class="${get_multiplier_badge_class(result.data[i].multiplication)} custom-badge mx-auto"> ${result.data[i].multiplication}x </div>`;
                    } else {
                        var multiplier = `-`;
                    }

                    $("#member_bet .mCSB_container").append(`
                        <div class="list-items bet_record_count ${result.data[i].cash_out_amount > 0 ? 'active' : ''}">
                            <div class="column-1 users fw-normal">
                                ${result.data[i].date}
                            </div>
                            <div class="column-2">
                                <button class="btn btn-transparent previous-history d-flex align-items-center mx-auto fw-normal">
                                    ${result.data[i].bet_amount + currency_symbol}
                                </button>
                            </div>
                            <div class="column-3">
                                ${multiplier}
                            </div>
                            <div class="column-4 fw-normal">
                                ${result.data[i].cash_out_amount > 0 ? result.data[i].cash_out_amount + currency_symbol : '-'} 
                            </div>
                        </div>
                    `);
                }
            }
            if (length < 10) {
                $("#load_btn").hide();
            } else {
                $("#load_btn").show();
            }
        }
    })
}

$("#main_auto_bet").on('change', function () {
    let isChecked = $(this).prop('checked');
    let section_no = 0;
    const isCheckedCashout = $("#main_checkout").prop('checked');
    
    // Save auto-bet state to localStorage
    if (typeof saveAutoBetState === 'function') {
        saveAutoBetState();
    }

    if (isChecked) {
        $("#main_bet_section").find("#bet_button").hide();
        $("#main_bet_section").find("#cancle_button").show();
        $("#main_bet_section").find("#cancle_button #waiting").show();
        if (is_game_generated == 1) {
            setTimeout(() => {
                $("#main_bet_section").find("#cancle_button #waiting").hide();
            }, 500);
        }
        $("#main_bet_section").find("#cashout_button").hide();
        $("#main_bet_section .controls").addClass('bet-border-red');
        $("#main_bet_section").parent().parent().find('.cashout-spinner-wrapper input').prop('disabled', true)
        $("#main_bet_section").find('.controls .navigation').addClass('stop-action')
        var bet_type = $("#main_bet_now").parent().parent().parent().find(".navigation #bet_type").val(); // 0 - Normal, 1 - Auto
        let bet_amount = $("#main_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val();

        if (bet_amount < min_bet_amount || bet_amount == '' || bet_amount == NaN) {
            bet_amount = parseFloat(min_bet_amount).toFixed(2);
        } else if (bet_amount > max_bet_amount) {
            bet_amount = parseFloat(max_bet_amount).toFixed(2);
        } else {
            bet_amount = parseFloat(bet_amount).toFixed(2);
        }

        $("#main_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val(bet_amount);

        if (bet_amount >= min_bet_amount && bet_amount <= max_bet_amount) {
            if (bet_array.length == 1) {
                if (bet_array[0].section_no != section_no) {
                    bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
                }
            } else if (bet_array.length == 2) {
                if (bet_array[0].section_no != section_no && bet_array[1].section_no != section_no) {
                    bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
                }
            } else {
                bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
            }
            
            // ========== AUTO-BET TOGGLE INTENT (FIRST BET PLACER) ==========
            // Send betIntent when auto-bet is turned ON
            // isAutoBet=true marks this as FIRST BET PLACER (committed before round)
            try {
                if (sendBetIntent(bet_amount, true)) {
                    console.log('⚡ AUTO-BET TOGGLE INTENT (FIRST PLACER) sent for main section');
                }
            } catch (e) { /* ignore errors */ }
            // ===============================================================
        }

        $(".main_bet_amount").prop('disabled', true);
        $("#main_plus_btn").prop('disabled', true);
        $("#main_minus_btn").prop('disabled', true);
        $(".main_amount_btn").prop('disabled', true);
        $("#main_checkout").prop('disabled', true);
        if ($("#main_checkout").prop('checked')) {
            $("#main_incrementor").prop('disabled', true);
        }

    } else {
        if (isCheckedCashout == false) {
            $("#main_bet_section").parent().parent().find('.cashout-spinner-wrapper input').prop('disabled', false)
            $("#main_bet_section").find('.controls .navigation').removeClass('stop-action')
        } else {
            $("#main_bet_section").parent().parent().find('.cashout-spinner-wrapper input').prop('disabled', true)
            $("#main_bet_section").find('.controls .navigation').addClass('stop-action')
        }
        // if(bet_array.length == 1) {
        //     bet_array.splice(0, 1); // Remove Perticular Bet
        // } else if (bet_array.length == 2 && section_no == 0) {
        //     bet_array.splice(0, 1); // Remove Perticular Bet
        // } else if (bet_array.length == 2 && section_no == 1) {
        //     bet_array.splice(1, 1); // Remove Perticular Bet
        // }

        if (bet_array.length == 1) {
            bet_array.splice(0, 1); // Remove Perticular Bet
        } else if (bet_array.length == 2 && section_no == 0) {
            if (bet_array[0].section_no == 0) {
                bet_array.splice(0, 1); // Remove Perticular Bet
            } else {
                bet_array.splice(1, 1); // Remove Perticular Bet
            }
        } else if (bet_array.length == 2 && section_no == 1) {
            if (bet_array[0].section_no == 1) {
                bet_array.splice(0, 1); // Remove Perticular Bet
            } else {
                bet_array.splice(1, 1); // Remove Perticular Bet
            }
        }

        $("#main_bet_section").find("#bet_button").show();
        $("#main_bet_section").find("#cancle_button").hide();
        $("#main_bet_section").find("#cancle_button #waiting").hide();
        $("#main_bet_section").find("#cashout_button").hide();
        $("#main_bet_section .controls").removeClass('bet-border-red');

        $(".main_bet_amount").prop('disabled', false);
        $("#main_plus_btn").prop('disabled', false);
        $("#main_minus_btn").prop('disabled', false);
        $(".main_amount_btn").prop('disabled', false);
        $("#main_checkout").prop('disabled', false)
        if ($("#main_checkout").prop('checked')) {
            $("#main_incrementor").prop('disabled', false);
        }
    }

});

$("#extra_auto_bet").on('change', function () {
    let isChecked = $(this).prop('checked');
    let section_no = 1;
    const isCheckedCashout = $('#extra_checkout').prop('checked');
    
    // Save auto-bet state to localStorage
    if (typeof saveAutoBetState === 'function') {
        saveAutoBetState();
    }

    if (isChecked) {
        $("#extra_bet_section").find("#bet_button").hide();
        $("#extra_bet_section").find("#cancle_button").show();
        $("#extra_bet_section").find("#cancle_button #waiting").show();
        if (is_game_generated == 1) {
            setTimeout(() => {
                $("#extra_bet_section").find("#cancle_button #waiting").hide();
            }, 500);
        }
        $("#extra_bet_section").find("#cashout_button").hide();
        $("#extra_bet_section .controls").addClass('bet-border-red');
        $("#extra_bet_section").parent().parent().find('.cashout-spinner-wrapper input').prop('disabled', true)
        $("#extra_bet_section").find('.controls .navigation').addClass('stop-action')
        var bet_type = $("#extra_bet_now").parent().parent().parent().find(".navigation #bet_type").val(); // 0 - Normal, 1 - Auto
        let bet_amount = $("#extra_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val();

        if (bet_amount < min_bet_amount || bet_amount == '' || bet_amount == NaN) {
            bet_amount = parseFloat(min_bet_amount).toFixed(2);
        } else if (bet_amount > max_bet_amount) {
            bet_amount = parseFloat(max_bet_amount).toFixed(2);
        } else {
            bet_amount = parseFloat(bet_amount).toFixed(2);
        }

        $("#extra_bet_now").parent().parent().find(".bet-block .spinner #bet_amount").val(bet_amount);

        if (bet_amount >= min_bet_amount && bet_amount <= max_bet_amount) {

            if (bet_array.length == 1) {
                if (bet_array[0].section_no != section_no) {
                    bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
                }
            } else if (bet_array.length == 2) {
                if (bet_array[0].section_no != section_no && bet_array[1].section_no != section_no) {
                    bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
                }
            } else {
                bet_array.push({ bet_type: bet_type, bet_amount: bet_amount, section_no: section_no });
            }
            
            // ========== AUTO-BET TOGGLE INTENT (FIRST BET PLACER) ==========
            // Send betIntent when auto-bet is turned ON
            // isAutoBet=true marks this as FIRST BET PLACER (committed before round)
            try {
                if (sendBetIntent(bet_amount, true)) {
                    console.log('⚡ AUTO-BET TOGGLE INTENT (FIRST PLACER) sent for extra section');
                }
            } catch (e) { /* ignore errors */ }
            // ===============================================================
        }
        $(".extra_bet_amount").prop('disabled', true);
        $("#extra_minus_btn").prop('disabled', true);
        $("#extra_plus_btn").prop('disabled', true);
        $(".extra_amount_btn").prop('disabled', true);
        $("#extra_checkout").prop('disabled', true);
        if ($("#extra_checkout").prop('checked')) {
            $("#extra_incrementor").prop('disabled', true);
        }


    } else {
        if (isCheckedCashout == false) {
            $("#extra_bet_section").parent().parent().find('.cashout-spinner-wrapper input').prop('disabled', false)
            $("#extra_bet_section").find('.controls .navigation').removeClass('stop-action')
        } else {
            $("#extra_bet_section").parent().parent().find('.cashout-spinner-wrapper input').prop('disabled', true)
            $("#extra_bet_section").find('.controls .navigation').addClass('stop-action')
        }

        if (bet_array.length == 1) {
            bet_array.splice(0, 1); // Remove Perticular Bet
        } else if (bet_array.length == 2 && section_no == 0) {
            if (bet_array[0].section_no == 0) {
                bet_array.splice(0, 1); // Remove Perticular Bet
            } else {
                bet_array.splice(1, 1); // Remove Perticular Bet
            }
        } else if (bet_array.length == 2 && section_no == 1) {
            if (bet_array[0].section_no == 1) {
                bet_array.splice(0, 1); // Remove Perticular Bet
            } else {
                bet_array.splice(1, 1); // Remove Perticular Bet
            }
        }

        $("#extra_bet_section").find("#bet_button").show();
        $("#extra_bet_section").find("#cancle_button").hide();
        $("#extra_bet_section").find("#cancle_button #waiting").hide();
        $("#extra_bet_section").find("#cashout_button").hide();
        $("#extra_bet_section .controls").removeClass('bet-border-red');

        $(".extra_bet_amount").prop('disabled', false);
        $("#extra_minus_btn").prop('disabled', false);
        $("#extra_plus_btn").prop('disabled', false);
        $(".extra_amount_btn").prop('disabled', false);
        $("#extra_checkout").prop('disabled', false);
        if ($("#extra_checkout").prop('checked')) {
            $("#extra_incrementor").prop('disabled', false);
        }

    }

});


/*-----------------HINAL-----------------*/
function soundPlay() {
    let sound = document.getElementById("sound_Audio");
    if (document.hidden) {
        sound.pause();
    } else {
        if ($("#sound").prop("checked") == true) {
            if (window_blur == 0) {
                window.audioHandler.safePlay(sound).catch(e => console.log('Audio play prevented:', e));
            } else {
                sound.pause();
            }

        } else {
            sound.pause();
        }
    }
}

function flyPlaneSound() {
    let sound = document.getElementById("fly_plane_audio");
    if (document.hidden) {
        sound.pause();
    } else {
        if ($("#sound").prop("checked") == true) {
            if (window_blur == 0) {
                window.audioHandler.safePlay(sound).catch(e => console.log('Audio play prevented:', e));
            } else {
                sound.pause();
            }
        } else {
            sound.pause();
        }
    }
}

function cashOutSound() {
    let sound = document.getElementById("cash_out_audio");
    if (document.hidden) {
        sound.pause();
    } else {
        if ($("#sound").prop("checked") == true) {
            if (window_blur == 0) {
                window.audioHandler.safePlay(sound).catch(e => console.log('Audio play prevented:', e));
            } else {
                sound.pause();
            }
        } else {
            sound.pause();
        }
    }
}

function cashOutSoundOtherSection() {
    let sound = document.getElementById("cash_out_audio_2");
    if (document.hidden) {
        sound.pause();
    } else {
        if ($("#sound").prop("checked") == true) {
            if (window_blur == 0) {
                window.audioHandler.safePlay(sound).catch(e => console.log('Audio play prevented:', e));
            } else {
                sound.pause();
            }
        } else {
            sound.pause();
        }
    }
}

function backgroundSound() {
    let music = document.getElementById("background_Audio");
    if ($("#music").prop("checked") == true) {
        music.volume = 0.5;
        music.autoplay = true;
        music.loop = true;
        music.load();
    } else {
        music.pause();
    }
}

backgroundSound();
$("#music").on('change', function () {
    backgroundSound();
})

$(".main_bet_btn").on('click', function () {
    if (stage_time_out != 1) {
        let id = $(this).attr('id');
        if (id == 'main_bet_now') {
            $(".main_bet_amount").prop('disabled', true);
            $("#main_plus_btn").prop('disabled', true);
            $("#main_minus_btn").prop('disabled', true);
            $(".main_amount_btn").prop('disabled', true);
            $("#main_checkout").prop('disabled', true);
            $("#main_incrementor").prop('disabled', true);

        } else if (id == 'main_cancel_now') {
            $(".main_bet_amount").prop('disabled', false);
            $("#main_plus_btn").prop('disabled', false);
            $("#main_minus_btn").prop('disabled', false);
            $(".main_amount_btn").prop('disabled', false);
            $("#main_checkout").prop('disabled', false)
            if ($("#main_checkout").prop('checked')) {
                $("#main_incrementor").prop('disabled', false);
            }

        }

        if (id == 'extra_bet_now') {
            $(".extra_bet_amount").prop('disabled', true);
            $("#extra_minus_btn").prop('disabled', true);
            $("#extra_plus_btn").prop('disabled', true);
            $(".extra_amount_btn").prop('disabled', true);
            $("#extra_checkout").prop('disabled', true);
            $("#extra_incrementor").prop('disabled', true);
        } else if (id == 'extra_cancel_now') {
            $(".extra_bet_amount").prop('disabled', false);
            $("#extra_minus_btn").prop('disabled', false);
            $("#extra_plus_btn").prop('disabled', false);
            $(".extra_amount_btn").prop('disabled', false);
            $("#extra_checkout").prop('disabled', false);
            if ($("#extra_checkout").prop('checked')) {
                $("#extra_incrementor").prop('disabled', false);
            }

        }
    }
});

function check_login_status() {
    $.ajax({
        url: 'is_login',
        type: "POST",
        dataType: "json",
        success: function (result) {
            if (result.isSuccess == false) {
                window.location.href = 'login';
            }
        }
    });
}

function gameLoadingTimer() {
    let timer_no = 1;
    var game_loading_timer = setInterval(function () {
        if (timer_no <= 5) {
            if (timer_no == 1) {
                $('.loading-game').addClass('show');
            }
            timer_no++;
        } else {
            $(".loading-game").removeClass('show');
            clearInterval(game_loading_timer);
        }
    }, 1000); // for every second
}

let focus_timer = 0;
let focus_interval;
let visibility_timer = 0;
let visibility_interval;

$(window).focus(function () {
    // console.log('focused');
    // console.log(focus_timer);
    // DISABLED: Auto-reload removed - socket keeps game in sync
    // if (focus_timer > 10) {
    //     location.reload();
    // } else {
        focus_timer = 0;
        clearInterval(focus_interval);
    // }
});

let window_blur = 0;
$(window).blur(function () {
    // console.log('blur');
    const music = document.getElementById("background_Audio");
    window_blur = 1;
    music.pause();
    focus_interval = setInterval(function () {
        focus_timer = parseInt(focus_timer + 1);
    }, 1000);
});


$(window).focus(function () {
    // console.log('blur');
    window_blur = 0;
    const music = document.getElementById("background_Audio");
    window.audioHandler.safePlay(music).catch(e => console.log('Audio play prevented:', e));

});
document.addEventListener('visibilitychange', function (event) {
    if (document.hidden) {
        // console.log('not visible');
        visibility_interval = setInterval(function () {
            visibility_timer = parseInt(visibility_timer + 1);
        }, 1000);
    } else {
        // console.log(visibility_timer);
        // console.log('is visible');
        // DISABLED: Auto-reload removed - socket keeps game in sync
        // if (visibility_timer > 10) {
        //     location.reload();
        // } else {
            visibility_timer = 0;
            clearInterval(visibility_interval);
        // }
    }
});

function enableDisable(section) {
    $(`#${section}`).find('.controls').addClass('dullEffect');
    setTimeout(function () {
        $(`#${section}`).find('.controls').removeClass('dullEffect');
    }, 200);
}

//khushbu for validate auto cash textbox
function main_incrementor_change(new_value) {
    if (new_value < 1.01) {
        $("#main_incrementor").val("1.01");
    } else {
        $("#main_incrementor").val(parseFloat(new_value).toFixed(2));
    }
}
function extra_incrementor_change(new_value) {
    if (new_value < 1.01) {
        $("#extra_incrementor").val("1.01");
    } else {
        $("#extra_incrementor").val(parseFloat(new_value).toFixed(2));
    }
}
function hide_loading_game() {
    $('.loading-game').removeClass('show');
}
// khushbu for when come from minimize refresh page
$(window).bind("pageshow", function (event) {
    if (event.originalEvent.persisted) {
        $(".load-txt").show();
    }
});

$(".fill-line").bind('oanimationend animationend webkitAnimationEnd', function () {
    // console.log('-----anime---end-----');
    $(".game-centeral-loading").hide();
    $('bottom-left-plane').show();
});

$(".fill-line").bind('oanimationstart animationstart webkitAnimationStart', function () {
    // console.log('-----anime---start-----');
    $(".game-centeral-loading").show();
});

/*-------HINAL (START)-------*/

$(document).click(function () {
    if ($(".button-block").hasClass('show')) {
        $(".button-block").removeClass('show');
    }
});

$(".history-top").click(function (e) {
    e.stopPropagation(); // This is the preferred method.
    return false;        // This should not be used unless you do not want
});

/*-------HINAL (END)-------*/
