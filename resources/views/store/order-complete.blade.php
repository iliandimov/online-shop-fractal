@extends('store.layout')

@section('title', 'Поръчката е приета — Forme')

@section('content')
<section class="wrap order-success">
    <div class="success-mark">✓</div>
    <div class="eyebrow">БЛАГОДАРИМ ТИ</div>
    <h1>Поръчката е приета.</h1>
    <p>Поръчка <b>{{ $order['number'] }}</b> за <b>{{ number_format($order['total'], 2, ',', ' ') }} €</b> е записана.</p>
    <div class="success-card">
        <span>Плащане</span><b>Наложен платеж</b>
        <span>Следваща стъпка</span><b>Ще се свържем за потвърждение и доставка.</b>
    </div>
    <a class="button button-dark" href="{{ route('home') }}">Обратно към магазина <span>↗</span></a>
</section>
@endsection
