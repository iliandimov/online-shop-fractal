@extends('store.layout')

@section('title', 'FORME — добрите находки започват тук')

@section('content')
<section class="hero wrap">
    <div class="hero-copy">
        <div class="eyebrow"><i></i> ПОДБРАНО С УСЕЩАНЕ</div>
        <h1>Всеки ден<br>започва с <em>добър избор.</em></h1>
        <p>Любими находки за движение, работа и свободно време. Избрани с мисъл за качество и създадени да останат.</p>
        <a class="button button-dark" href="#products">Разгледай колекцията <span>↗</span></a>
        <div class="hero-trust"><div class="trust-avatars"><i>Д</i><i>М</i><i>А</i></div><span><b>4.9 / 5</b> от доволни клиенти</span></div>
    </div>
    <div class="hero-visual">
        <div class="hero-photo"></div>
        <div class="hero-sticker"><span>НОВА<br>КОЛЕКЦИЯ</span><b>’26</b></div>
        <div class="hero-caption"><span>01 / 03</span><b>Стил за всеки ден</b><span>→</span></div>
    </div>
</section>

<section class="benefits wrap">
    <div><span class="benefit-icon">✳</span><div><b>Подбрано качество</b><small>Проверени продукти</small></div></div>
    <div><span class="benefit-icon">⌁</span><div><b>Лесно връщане</b><small>До 30 дни за размисъл</small></div></div>
    <div><span class="benefit-icon">↗</span><div><b>Бърза доставка</b><small>До адрес, офис или автомат</small></div></div>
    <div><span class="benefit-icon">♡</span><div><b>Истинска грижа</b><small>Помагаме при всеки въпрос</small></div></div>
</section>

<section class="collection-section wrap" id="products">
    <div class="section-heading"><div><div class="eyebrow">ИЗБРАНО ЗА ТЕБ</div><h2>Любими находки</h2></div><a class="text-link" href="{{ route('category', 'obuvki') }}">Виж всички продукти <span>→</span></a></div>
    <div class="product-grid">
        @foreach($featured as $product)
            @include('store.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section class="editorial wrap">
    <div class="editorial-image"></div>
    <div class="editorial-copy"><div class="eyebrow">ПО-МАЛКО, НО ПО-ДОБРО</div><h2>Избирай неща,<br>които <em>остават.</em></h2><p>Функционални форми, спокойни цветове и качество, на което можеш да разчиташ.</p><a class="button button-light" href="{{ route('category', 'drehi') }}">Открий своя стил <span>↗</span></a></div>
</section>

<section class="collection-section wrap more-products">
    <div class="section-heading"><div><div class="eyebrow">ПРАКТИЧНО И НАДЕЖДНО</div><h2>За твоите проекти</h2></div><a class="text-link" href="{{ route('category', 'instrumenti') }}">Разгледай инструментите <span>→</span></a></div>
    <div class="product-grid product-grid-small">
        @foreach(array_slice($products, 2, 2, true) as $product)
            @include('store.partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section class="newsletter wrap"><div><div class="eyebrow">ПИСМА СЪС СМИСЪЛ</div><h2>Добри неща<br>пристигат във входящата.</h2></div><form action="#" method="get"><label for="newsletter-email">Имейл адрес</label><div class="newsletter-input"><input id="newsletter-email" type="email" placeholder="твоят@email.com"><button type="button" aria-label="Абонирай се">→</button></div><small>Нови колекции, добри идеи и от време на време изненада.</small></form></section>
@endsection
