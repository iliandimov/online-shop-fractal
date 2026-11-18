<article class="product-card">
    <a class="product-image" href="{{ route('product', $product['slug']) }}" style="--product-image:url('{{ $product['media_url'] ?: 'https://images.unsplash.com/'.$product['image'].'?auto=format&fit=crop&w=900&q=85' }}')">
        @if($product['badge'])<span class="product-badge">{{ $product['badge'] }}</span>@endif
        <span class="quick-view">Разгледай <span>↗</span></span>
    </a>
    <div class="product-meta"><span>{{ $product['category'] }}</span><span class="stock-dot">В наличност</span></div>
    <a class="product-name" href="{{ route('product', $product['slug']) }}">{{ $product['name'] }}</a>
    <div class="price-row"><strong>{{ number_format($product['price'], 2, ',', ' ') }} €</strong>@if($product['old_price'])<del>{{ number_format($product['old_price'], 2, ',', ' ') }} €</del>@endif</div>
</article>
