@extends('Layout.usergame')
@section('content')
    <div class="row gy-5" style="padding-top: 40px;">
        <div class="col-lg-6">
            <div class="headtail-body">
                @include('partials.game_shape')
                <div class="headtail-body__flip">
                    <div class="coin-flipbox">
                        <div class="flp">
                            <div id="coin-flip-cont">
                                <div class="flipcoin" id="coin">
                                    <div class="flpng coins-wrapper">
                                        <div class="front"><img
                                                src="{{ asset('assets/xaxino/images/games/head.png') }}"
                                                alt="im"></div>
                                        <div class="back"><img
                                                src="{{ asset('assets/xaxino/images/games/tail.png') }}"
                                                alt="im"></div>
                                    </div>
                                    <div class="headCoin d-none">
                                        <div class="front"><img
                                                src="{{ asset('assets/xaxino/images/games/head.png') }}"
                                                alt="im"></div>
                                        <div class="back"><img
                                                src="{{ asset('assets/xaxino/images/games/tail.png') }}"
                                                alt="im"></div>
                                    </div>
                                    <div class="tailCoin d-none">
                                        <div class="front"><img
                                                src="{{ asset('assets/xaxino/images/games/tail.png') }}"
                                                alt="im"></div>
                                        <div class="back"><img
                                                src="{{ asset('assets/xaxino/images/games/head.png') }}"
                                                alt="im"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="headtail-wrapper">
                <h4 class="game-contet-title">
                    {{ $isDemo ? trans('Demo Balance:') : trans('Current Balance:') }}
                    <span class="text bal">{{ showAmount($balance, currencyFormat: false) }}</span>
                    {{ __(gs('cur_text')) }}
                </h4>
                <form id="game" method="post">
                    @csrf
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                            <input type="number" step="any" class="form-control form--control"
                                placeholder="@lang('Enter amount')" name="invest" value="{{ old('invest') }}">
                            <button type="button" class="input-group-text minmax-btn minBtn">@lang('Min')</button>
                            <button type="button" class="input-group-text minmax-btn maxBtn">@lang('Max')</button>
                        </div>

                        <small class="fw-light mt-3 d-inline-block input-inner-note">
                            <i class="fas fa-info-circle mr-2"></i>
                            @lang('Minimum'): {{ showAmount($game->min_limit) }} |
                            @lang('Maximum'): {{ showAmount($game->max_limit) }} |
                            <span class="text--warning">
                                @lang('Win Amount')
                                @if ($game->invest_back == 1)
                                    {{ getAmount($game->win + 100) }}%
                                @else
                                    {{ getAmount($game->win) }}%
                                @endif
                            </span>
                        </small>
                    </div>

                    <div class="headtail-slect">
                        <div class="headtail-slect__box game-select-box">
                            <div class="headtail-slect__image single-select head gmimg">
                                <img src="{{ asset('assets/xaxino/images/games/head.png') }}" alt="game-image">
                            </div>
                        </div>
                        <div class="headtail-slect__box game-select-box">
                            <div class="headtail-slect__image single-select tail gmimg">
                                <img src="{{ asset('assets/xaxino/images/games/tail.png') }}" alt="game-image">
                            </div>
                        </div>
                        <input name="choose" type="hidden">
                    </div>
                    <div class="form-submit game-playbtn">
                        <button type="submit" id="flip" class="btn btn--gradient w-100">@lang('Play Now')</button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 w-100">
                        <button type="button" class="d-block text-white text-center mx-auto" data-bs-toggle="modal"
                            data-bs-target="#exampleModalCenter"><i class="fas fa-info-circle mr-2"></i>
                            @lang('Game Instruction')
                        </button>
                        <button type="button" class="sound--btn audioBtn">
                            <i class="fas fa-volume-up"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal custom--modal fade" id="exampleModalCenter">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content section--bg">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">@lang('Game Rule')</h5>
                    <span class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    @php echo $game->instruction @endphp
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/xaxino/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/xaxino/css/custom.css') }}">
    <link href="{{ asset('assets/global/css/game/coinflipping.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/soundControl.js') }}"></script>
    <script src="{{ asset('assets/global/js/game/game.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            const HeadTailGame = {
                investField: $("[name=invest]"),
                minLimit: Number("{{ $game->min_limit }}"),
                maxLimit: Number("{{ $game->max_limit }}"),
                currency: "{{ gs('cur_text') }}",
                spinningTime: Number("{{ @$game->level['spinning_time'] ?? 5 }}"),
                investUrl: "{{ route('game.invest', ['head_tail', @$isDemo]) }}",
                gameEndUrl: "{{ route('game.end', ['head_tail', @$isDemo]) }}",
                audioAssetPath: "{{ asset('assets/audio') }}",
                timerA: null,
                gameLogId: null,
                isRequest: false,

                init: function() {
                    this.bindEvents();
                    this.resetCoin();
                },

                resetCoin: function() {
                    $(".flipcoin").removeClass("animate animateClick").css({
                        "animation": "none",
                        "-webkit-animation": "none",
                        "transform": "none"
                    });
                    setTimeout(() => {
                        $(".flipcoin").addClass("animate").css({
                            "animation": "",
                            "-webkit-animation": "",
                            "transform": ""
                        });
                    }, 1);
                },

                bindEvents: function() {
                    const self = this;
                    
                    $(".minBtn").on('click', function() {
                        if (typeof playAudio === 'function') playAudio(self.audioAssetPath, "click.mp3");
                        self.investField.val(self.minLimit);
                    });

                    $(".maxBtn").on('click', function() {
                        if (typeof playAudio === 'function') playAudio(self.audioAssetPath, "click.mp3");
                        self.investField.val(self.maxLimit);
                    });

                    $(".head").click(function () {
                        $("input[name=choose]").val("head");
                        $(this).addClass("active");
                        $(".tail").removeClass("active");
                        if (typeof playAudio === 'function') playAudio(self.audioAssetPath, "click.mp3");
                    });

                    $(".tail").click(function () {
                        $("input[name=choose]").val("tail");
                        $(this).addClass("active");
                        $(".head").removeClass("active");
                        if (typeof playAudio === 'function') playAudio(self.audioAssetPath, "click.mp3");
                    });

                    $('#game').on('submit', function(e) {
                        e.preventDefault();
                        self.playGame();
                    });
                },

                playGame: function() {
                    if (this.isRequest) return;
                    
                    const invest = this.investField.val();
                    const choose = $("input[name=choose]").val();

                    if (!invest) { notify('error', 'Invest field is required'); return; }
                    if (!choose) { notify('error', 'Please select a coin side'); return; }
                    if (Number(invest) < this.minLimit) { notify('error', `Minimum invest is ${this.minLimit} ${this.currency}`); return; }
                    if (Number(invest) > this.maxLimit) { notify('error', `Maximum invest is ${this.maxLimit} ${this.currency}`); return; }

                    this.isRequest = true;
                    this.beforeProcess();
                    
                    if (typeof playAudio === 'function') playAudio(this.audioAssetPath, "coin.mp3");
                    
                    const self = this;
                    $.post(this.investUrl, {
                        _token: "{{ csrf_token() }}",
                        invest: invest,
                        choose: choose
                    }, function(data) {
                        if (GameUtils.checkErrors(data, () => { self.successOrError(); })) return;

                        $(".bal").text(data.balance);
                        if (window.updateWalletBalance) window.updateWalletBalance(data.balance);

                        self.startGame(data);
                    });
                },

                beforeProcess: function() {
                    $('.flipcoin').removeClass('animateClick animate').css({
                        "animation": "",
                        "-webkit-animation": "",
                        "transform": ""
                    });
                    $('.flpng').removeClass('d-none').css("animation", "");
                    $('#coin .headCoin').addClass('d-none');
                    $('#coin .tailCoin').addClass('d-none');
                    $('#flip').html('<i class="la la-gear fa-spin"></i> Processing...').prop('disabled', true);
                },

                startGame: function(data) {
                    this.gameLogId = data.game_log_id;
                    $(".flipcoin").addClass("animateClick");
                    $(".flpng").addClass("animate");
                    $('#flip').html('<i class="la la-gear fa-spin"></i> Playing...');
                    
                    const self = this;
                    this.timerA = setInterval(function() {
                        self.complete(data);
                    }, this.spinningTime * 1000);
                },

                complete: function(data) {
                    const self = this;
                    $.post(this.gameEndUrl, {
                        _token: "{{ csrf_token() }}",
                        game_log_id: data.game_log_id
                    }, function(response) {
                        if (GameUtils.checkErrors(response, () => { 
                            self.successOrError();
                            clearInterval(self.timerA);
                        })) return;

                        self.gameFinish(response);
                    });
                },

                gameFinish: function(data) {
                    clearInterval(this.timerA);
                    
                    if (typeof audioPause === 'function') audioPause();

                    $(".flipcoin").removeClass("animateClick animate").css({
                        "animation": "none",
                        "-webkit-animation": "none",
                        "transform": "none"
                    });
                    $(".flpng").addClass("d-none").css("animation", "none");
                    
                    GameUtils.setPopup(data, this.audioAssetPath);
                    $(".bal").text(data.bal);
                    if (window.updateWalletBalance) window.updateWalletBalance(data.bal);
                    
                    this.showResult(data);
                    this.successOrError();
                },

                showResult: function(data) {
                    if (data.result == "head") {
                        $(".headCoin").removeClass("d-none");
                        $(".headCoin").find(".front").removeClass("d-none");
                        $(".headCoin").find(".back").addClass("d-none");
                        $(".tailCoin").addClass("d-none");
                        $(".flpng").addClass("d-none");
                    } else {
                        $(".tailCoin").removeClass("d-none");
                        $(".tailCoin").find(".back").addClass("d-none");
                        $(".tailCoin").find(".front").removeClass("d-none");
                        $(".headCoin").addClass("d-none");
                        $(".flpng").addClass("d-none");
                    }
                },

                successOrError: function() {
                    $(".gmimg").removeClass("active");
                    $(".game-select-box").removeClass("active");
                    $('#flip').html("Play Now").prop("disabled", false);
                    this.isRequest = false;
                }
            };

            $(document).ready(function() {
                HeadTailGame.init();
            });

        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        /* Shared Glassmorphism Logic for All Views */
        .headtail-body, .headtail-wrapper {
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(15px) !important;
            -webkit-backdrop-filter: blur(15px) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 24px !important;
            padding: 45px !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 450px;
        }

        /* Specific alignment for the control panel content */
        .headtail-wrapper {
            align-items: stretch !important;
        }

        /* Responsive height adjustments */
        @media(max-width: 991px) {
            .headtail-body, .headtail-wrapper {
                min-height: 400px;
                padding: 30px !important;
            }
        }

        /* Neon Selection Glow */
        .gmimg {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            filter: grayscale(0.6) brightness(0.8);
            opacity: 0.6;
            border: 2px solid transparent !important;
            padding: 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.02);
        }
        
        .gmimg.active {
            filter: grayscale(0) brightness(1.2);
            opacity: 1;
            border: 2px solid #ff9500 !important;
            box-shadow: 0 0 35px rgba(255, 149, 0, 0.4), inset 0 0 15px rgba(255, 149, 0, 0.2);
            transform: scale(1.1);
            background: rgba(255, 149, 0, 0.1);
        }

        /* Premium Input Styling */
        .form--control {
            background: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-radius: 10px !important;
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #ff9500 !important;
        }

        /* High-End Play Button */
        #flip {
            background: linear-gradient(135deg, #ff9500 0%, #ff5e00 100%);
            border: none;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            height: 55px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(255, 94, 0, 0.3);
            transition: all 0.3s ease;
            color: #000;
        }

        #flip:hover:not(:disabled) {
            box-shadow: 0 0 20px rgba(255, 94, 0, 0.6);
            transform: translateY(-2px);
        }

        /* Wallet and Title Labels */
        .game-contet-title {
            color: #8a9cb1;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }
        .text.bal {
            color: #ff9500;
            font-weight: 900;
            font-size: 1.4rem;
        }

        #coin,
        .coins-wrapper,
        #coin .front,
        #coin .back,
        #coin-flip-cont,
        .flp {
            width: 200px;
            height: 200px;
        }

        @media(max-width: 991px) {
            #coin,
            .coins-wrapper,
            #coin .front,
            #coin .back,
            #coin-flip-cont,
            .flp {
                width: 300px;
                height: 300px;
            }
        }


        @media(max-width: 767px) {
            #coin,
            .coins-wrapper,
            #coin .front,
            #coin .back,
            #coin-flip-cont,
            .flp {
                width: 200px !important;
                height: 200px !important;
            }

            .headtail-body .coin-flipbox {
                width: 200px;
                height: 200px;
            }
        }

        @media(max-width: 425px) {
            #coin,
            .coins-wrapper,
            #coin .front,
            #coin .back,
            #coin-flip-cont,
            .flp {
                width: 120px !important;
                height: 120px !important;
            }

            .headtail-body .coin-flipbox {
                width: 120px;
                height: 120px;
            }
        }
    </style>
@endpush



