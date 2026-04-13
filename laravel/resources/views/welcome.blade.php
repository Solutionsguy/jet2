@extends('Layout.usergame')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/xaxino/css/main.css') }}">
<style>
    /* Mobile-First "Ken.bet" Style Dashboard */
    :root {
        --ken-bg: #0b162c;
        --ken-card: #15243d;
        --ken-accent: #ff003c; /* Red highlight */
        --ken-gold: #ff9500;
        --ken-text: #ffffff;
        --ken-muted: #8a9cb1;
        --ken-border: rgba(255, 255, 255, 0.05);
    }

    body {
        background-color: var(--ken-bg) !important;
        color: var(--ken-text);
        font-family: 'Inter', sans-serif;
    }

    .main-container {
        padding: 0 !important;
    }

    /* Edge-to-Edge Hero Carousel */
    .hero-wrapper {
        padding: 40px 0 10px; /* Increased to 40px to clear header */
        margin: 0 -6px; /* Offset the 6px container padding to go full width */
    }
    
    .dashboard-hero {
        width: 100%;
        margin: 0 auto;
        padding: 0; /* Removed side padding */
    }

    .owl-carousel .item {
        border-radius: 0; /* Sharp edges look better for full-width banners */
        overflow: hidden;
        border: none;
    }

    @media (min-width: 992px) {
        .hero-wrapper {
            margin: 0 -40px; /* Offset the larger desktop padding */
        }
    }

    /* Search Bar */
    .search-container {
        padding: 0 15px 15px;
    }
    
    .ken-search {
        position: relative;
        background: #1a2b4a;
        border-radius: 8px;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        border: 1px solid var(--ken-border);
    }
    
    .ken-search i {
        color: var(--ken-muted);
        margin-right: 10px;
    }
    
    .ken-search input {
        background: transparent;
        border: none;
        color: #fff;
        width: 100%;
        outline: none;
        font-size: 14px;
    }

    /* Horizontal Category Scroll */
    .category-scroll-wrapper {
        padding: 0 15px 20px;
        overflow-x: auto;
        display: flex;
        gap: 10px;
        scrollbar-width: none;
    }
    .category-scroll-wrapper::-webkit-scrollbar { display: none; }
    
    .ken-tab {
        white-space: nowrap;
        padding: 10px 20px;
        background: #1a2b4a;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    
    .ken-tab.active {
        background: linear-gradient(90deg, #ff003c, #bc002d);
        box-shadow: 0 4px 10px rgba(255, 0, 60, 0.3);
    }

    /* Section Headers */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 15px 15px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .view-all {
        color: var(--ken-muted);
        font-size: 13px;
        text-decoration: none !important;
    }

    /* Compact 3-Column Grid with Minimal Spacing */
    .game-grid-ken {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 columns */
        gap: 6px;
        padding: 0 6px 80px;
    }
    
    @media (min-width: 992px) {
        .game-grid-ken {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            padding: 0 40px 80px;
        }
        .dashboard-hero { padding: 0 40px; }
        .search-container, .category-scroll-wrapper, .section-header { padding-left: 40px; padding-right: 40px; }
    }

    .ken-game-card {
        background: var(--ken-card);
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s ease;
        height: 100%; /* Important for grid alignment */
    }
    
    .ken-game-card:active { transform: scale(0.98); }

    .ken-thumb-wrapper {
        position: relative;
        padding-top: 130%;
        overflow: hidden;
    }
    
    .ken-thumb-wrapper img {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover;
    }

    .favorite-star {
        position: absolute;
        top: 8px;
        right: 8px;
        color: rgba(255,255,255,0.5);
        font-size: 16px;
        z-index: 5;
    }

    .ken-game-info {
        padding: 8px 5px;
        text-align: center;
    }
    
    .provider-name {
        color: var(--ken-muted);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
    }

    /* Floating buttons */
    .floating-actions {
        position: fixed;
        right: 15px;
        bottom: 90px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 1000;
    }
    
    .float-btn {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.5);
    }
    .float-chat { background: #25D366; }

    /* Fix display issues for filtered items in Grid */
    .game-item {
        display: block;
        width: 100%;
    }

</style>
@endsection

@section('content')
<div class="hero-wrapper">
    <div class="dashboard-hero">
        <div class="owl-carousel owl-theme">
            <div class="item"><img src="{{ asset('images/01.png') }}" class="w-100" /></div>
            <div class="item"><img src="{{ asset('images/02.png') }}" class="w-100" /></div>
            <div class="item"><img src="{{ asset('images/03.png') }}" class="w-100" /></div>
        </div>
    </div>
</div>

<!-- Search Section -->
<div class="search-container">
    <div class="ken-search">
        <i class="fas fa-search"></i>
        <input type="text" id="game-search" placeholder="Search Games" />
    </div>
</div>

<!-- Categories Section -->
<div class="category-scroll-wrapper">
    <a href="javascript:void(0)" class="ken-tab active" data-filter="all">
        <i class="fas fa-gamepad"></i> Lobby
    </a>
    <a href="javascript:void(0)" class="ken-tab" data-filter="hot">
        <i class="fas fa-fire"></i> Hot
    </a>
    @foreach($categories as $category)
        <a href="javascript:void(0)" class="ken-tab" data-filter="cat-{{ $category->id }}">
            @if($category->icon) <i class="{{ $category->icon }}"></i> @endif
            {{ strtoupper(__($category->name)) }}
        </a>
    @endforeach
    @if($uncategorizedGames->count() > 0)
        <a href="javascript:void(0)" class="ken-tab" data-filter="cat-none">
            <i class="fas fa-plus"></i> Others
        </a>
    @endif
</div>

<!-- Section Header -->
<div class="section-header">
    <div class="section-title">
        <i class="fas fa-th-large text-warning"></i> Games
    </div>
    <a href="javascript:void(0)" class="view-all" onclick="$('.ken-tab[data-filter=all]').click()">View all <i class="fas fa-chevron-right ms-1"></i></a>
</div>

<!-- Game Grid -->
<div class="game-grid-ken">
    <!-- Static Aviator Card -->
    <div class="game-item all hot">
        <a href="{{ session()->has('userlogin') ? '/crash' : 'javascript:void(0)' }}" 
           class="ken-game-card" 
           @if(!session()->has('userlogin')) data-bs-toggle="modal" data-bs-target="#login-modal" @endif>
            <i class="far fa-star favorite-star"></i>
            <div class="ken-thumb-wrapper">
                <img src="{{ asset('images/aviator-img.png') }}" alt="Aviator" />
            </div>
            <div class="ken-game-info">
                <span class="provider-name">SPRIBE</span>
            </div>
        </a>
    </div>

    <!-- Categorized Games -->
    @foreach($categories as $category)
        @foreach($category->games as $game)
        <div class="game-item cat-{{ $category->id }} all @if($game->featured) hot @endif">
            <a href="{{ session()->has('userlogin') ? route('game.play', $game->alias) : 'javascript:void(0)' }}" 
               class="ken-game-card"
               @if(!session()->has('userlogin')) data-bs-toggle="modal" data-bs-target="#login-modal" @endif>
                <i class="far fa-star favorite-star"></i>
                <div class="ken-thumb-wrapper">
                    <img src="{{ asset($game->image) }}" alt="{{ $game->name }}" />
                </div>
                <div class="ken-game-info">
                    <span class="provider-name">{{ strtoupper(__($game->name)) }}</span>
                </div>
            </a>
        </div>
        @endforeach
    @endforeach

    <!-- Uncategorized Games -->
    @foreach($uncategorizedGames as $game)
    <div class="game-item cat-none all @if($game->featured) hot @endif">
        <a href="{{ session()->has('userlogin') ? route('game.play', $game->alias) : 'javascript:void(0)' }}" 
           class="ken-game-card"
           @if(!session()->has('userlogin')) data-bs-toggle="modal" data-bs-target="#login-modal" @endif>
            <i class="far fa-star favorite-star"></i>
            <div class="ken-thumb-wrapper">
                <img src="{{ asset($game->image) }}" alt="{{ $game->name }}" />
            </div>
            <div class="ken-game-info">
                <span class="provider-name">{{ strtoupper(__($game->name)) }}</span>
            </div>
        </a>
    </div>
    @endforeach
</div>

<!-- Floating Action Buttons -->
<div class="floating-actions d-md-none">
    <a href="https://wa.me/{{ setting('whatsapp_number') ?? '254123456789' }}?text={{ urlencode(setting('whatsapp_text') ?? 'Hello Support, I need help with...') }}" target="_blank" class="float-btn float-chat" style="text-decoration: none;">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>

@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Optimized Filtering Logic
        $('.ken-tab').on('click', function() {
            $('.ken-tab').removeClass('active');
            $(this).addClass('active');
            
            var filter = $(this).data('filter');
            
            // Hide all first
            $('.game-item').hide();
            
            // Show only matches
            if(filter === 'all') {
                $('.game-item').css('display', 'block');
            } else if(filter === 'hot') {
                $('.game-item.hot').css('display', 'block');
            } else {
                $('.game-item.' + filter).css('display', 'block');
            }
        });

        // Search - ensure display: block is used
        $('#game-search').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('.game-item').each(function() {
                var name = $(this).find('.provider-name').text().toLowerCase();
                if(name.indexOf(val) > -1) {
                    $(this).css('display', 'block');
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>
@endsection
