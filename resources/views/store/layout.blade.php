<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Подбрани продукти за всеки ден — с доставка до адрес, офис или автомат.">
    <title>@yield('title', 'FORME — магазин за добри находки')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
</head>
<body>
    <div class="announcement">Безплатна доставка над 75 € <span>·</span> 30 дни за връщане</div>
    <header class="site-header wrap">
        <a class="brand" href="{{ route('home') }}" aria-label="Начало">forme<span>.</span></a>
        <nav class="main-nav" aria-label="Основна навигация">
            @foreach($categories ?? [] as $category)
                <a href="{{ route('category', $category['slug']) }}">{{ $category['name'] }}</a>
            @endforeach
        </nav>
        <div class="header-actions">
            <a class="icon-link search-link" href="{{ route('home') }}#products" aria-label="Търсене"><svg viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg></a>
            <a class="cart-link" href="{{ route('cart') }}"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 8H6"/><circle cx="9.5" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span>Количка</span><b>{{ $cartCount ?? 0 }}</b></a>
        </div>
    </header>
    <main>
        @if(session('success'))<div class="flash wrap">{{ session('success') }}</div>@endif
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div><a class="brand footer-brand" href="{{ route('home') }}">forme<span>.</span></a><p>Подбрани неща за по-добро всекидневие.</p></div>
            <div><strong>Помощ</strong><a href="#">Доставка и връщане</a><a href="#">Често задавани въпроси</a></div>
            <div><strong>Свържи се с нас</strong><a href="mailto:hello@forme.bg">hello@forme.bg</a><span>Понеделник — петък, 9:00–17:00</span></div>
            <div class="footer-note">Плащане при доставка<br>Сигурна поръчка · Лесно връщане</div>
        </div>
        <div class="wrap footer-bottom"><span>© {{ date('Y') }} Forme. Всички права запазени.</span><span>Цените са с включено ДДС.</span></div>
    </footer>
</body>
</html>
