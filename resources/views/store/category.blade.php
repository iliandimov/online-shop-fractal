@extends('store.layout')

@section('title', $category['name'].' — Forme')

@section('content')
<section class="page-intro wrap"><div class="breadcrumbs"><a href="{{ route('home') }}">Начало</a><span>/</span><span>{{ $category['name'] }}</span></div><div class="eyebrow">КАТЕГОРИЯ</div><h1>{{ $category['name'] }}<em>.</em></h1><p>Подбрани {{ mb_strtolower($category['name']) }} за всеки ден.</p></section>
<section class="catalog wrap"><aside class="category-sidebar"><h3>Категории</h3>@foreach($categories as $item)<div class="category-tree"><a class="{{ $category['slug'] === $item['slug'] ? 'is-active' : '' }}" href="{{ route('category', $item['slug']) }}">{{ $item['name'] }} <span>→</span></a>@if($item['slug'] === $category['slug'])<ul>@foreach($item['children'] as $child)<li><span>{{ $child }}</span></li>@endforeach</ul>@endif</div>@endforeach<div class="filter-note"><span>+</span> Филтрирай продуктите</div></aside><div class="catalog-results"><div class="catalog-toolbar"><span>{{ count($products) }} продукта</span><span>Подредба: <b>Препоръчани⌄</b></span></div><div class="product-grid">@foreach($products as $product)@include('store.partials.product-card', ['product' => $product])@endforeach</div></div></section>
@endsection
