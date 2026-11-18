@extends('store.layout')

@section('title', $product['name'].' — Forme')

@section('content')
<div class="wrap product-page">
    <div class="breadcrumbs"><a href="{{ route('home') }}">Начало</a><span>/</span><a href="{{ route('category', $product['category_slug']) }}">{{ $product['category'] }}</a><span>/</span><span>{{ $product['name'] }}</span></div>
    <div class="product-detail">
        <div class="product-gallery"><div class="product-main-image" style="--product-image:url('{{ $product['media_url'] ?: 'https://images.unsplash.com/'.$product['image'].'?auto=format&fit=crop&w=1200&q=90' }}')"></div><div class="gallery-caption"><span>01 / 04</span><span>Увеличи изображението ↗</span></div><div class="product-thumbnails"><span class="is-current"></span><span></span><span></span><span></span></div></div>
        <div class="product-info"><div class="eyebrow">{{ strtoupper($product['category']) }} <span>·</span> {{ $product['sku'] }}</div><h1>{{ $product['name'] }}</h1><div class="rating">★★★★★ <span>4.9 · 28 отзива</span></div><div class="detail-price">{{ number_format($product['price'], 2, ',', ' ') }} € @if($product['old_price'])<del>{{ number_format($product['old_price'], 2, ',', ' ') }} €</del>@endif</div><p class="tax-note">Цената е с включено ДДС. Доставката се изчислява при поръчка.</p><p class="product-description">{{ $product['description'] }}</p>
            <form method="post" action="{{ route('cart.add') }}" class="add-form">@csrf<input type="hidden" name="slug" value="{{ $product['slug'] }}">
                @if($product['colors'])<div class="option-block"><div class="option-label">Цвят <b id="selected-color">{{ $product['colors'][0]['name'] }}</b></div><div class="swatches">@foreach($product['colors'] as $index => $color)<label class="swatch {{ $index === 0 ? 'is-selected' : '' }}" style="--swatch:{{ $color['hex'] }}" title="{{ $color['name'] }}"><input type="radio" name="color" value="{{ $color['name'] }}" {{ $index === 0 ? 'checked' : '' }}><span></span></label>@endforeach</div></div>@endif
                @if($product['sizes'])<div class="option-block"><div class="option-label">Размер <span class="size-guide">Таблица с размери ↗</span></div><div class="sizes">@foreach($product['sizes'] as $size)<label><input type="radio" name="size" value="{{ $size }}" required><span>{{ $size }}</span></label>@endforeach</div>@error('size')<small class="field-error">{{ $message }}</small>@enderror</div>@endif
                <div class="stock-message"><i></i> В наличност — изпращаме до 1–2 работни дни</div><div class="add-row"><div class="quantity-control"><button type="button" data-qty="minus" aria-label="Намали">−</button><input type="number" name="quantity" min="1" max="20" value="1" aria-label="Количество"><button type="button" data-qty="plus" aria-label="Увеличи">+</button></div><button class="button button-dark add-button" type="submit">Добави в количката <span>↗</span></button></div>
            </form><div class="product-perks"><div><span>↗</span> Доставка с куриер или до автомат</div><div><span>↺</span> 30 дни лесно връщане</div><div><span>♡</span> Плащане при доставка</div></div><details class="detail-accordion"><summary>Материали и характеристики <span>＋</span></summary><p>Подбрани материали, създадени за комфорт и продължителна употреба.</p></details><details class="detail-accordion"><summary>Доставка и връщане <span>＋</span></summary><p>Избери доставка до адрес, офис или автомат при завършване на поръчката.</p></details>
        </div>
    </div>
</div>
<section class="collection-section wrap related-products"><div class="section-heading"><div><div class="eyebrow">МОЖЕ ДА ТИ ХАРЕСА</div><h2>Още добри находки</h2></div></div><div class="product-grid">@foreach($related as $item)@include('store.partials.product-card', ['product' => $item])@endforeach</div></section>
<script>
document.querySelectorAll('.swatch input').forEach(input => input.addEventListener('change', () => {
    document.querySelectorAll('.swatch').forEach(item => item.classList.remove('is-selected'));
    input.closest('.swatch').classList.add('is-selected');
    document.getElementById('selected-color').textContent = input.value;
}));
document.querySelectorAll('[data-qty]').forEach(button => button.addEventListener('click', () => {
    const input = button.parentElement.querySelector('input');
    input.value = Math.max(1, Math.min(20, Number(input.value) + (button.dataset.qty === 'plus' ? 1 : -1)));
}));
</script>
@endsection
