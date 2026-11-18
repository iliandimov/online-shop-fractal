<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Url;

class StoreCatalog
{
    private const IMAGES = [
        'urban-runner' => 'photo-1542291026-7eec264c27ff',
        'essential-tee' => 'photo-1521572163474-6864f9cf17ab',
        'impact-drill' => 'photo-1504148455328-c376907d081c',
        'weekend-pack' => 'photo-1523381210434-271e8be1f52b',
    ];

    public function categories(): array
    {
        $groupId = LunarCollection::query()->whereHas('group', fn (Builder $query) => $query->where('handle', 'main'))->value('collection_group_id');

        if (! $groupId) {
            return [];
        }

        return LunarCollection::query()->inGroup($groupId)->defaultOrder()->get()->filter(fn (LunarCollection $item) => $item->parent_id === null)->map(function (LunarCollection $item) {
            return [
                'name' => $item->translateAttribute('name'),
                'slug' => $item->urls()->where('default', true)->value('slug'),
                'children' => $item->children->map(fn (LunarCollection $child) => $child->translateAttribute('name'))->all(),
            ];
        })->unique('slug')->values()->all();
    }

    public function products(?string $categorySlug = null): array
    {
        $categoryIds = null;
        if ($categorySlug) {
            $category = LunarCollection::query()->whereHas('urls', fn (Builder $query) => $query->where('slug', $categorySlug))->first();
            $categoryIds = $category ? $category->descendantsAndSelf($category->getKey())->pluck('id') : collect([-1]);
        }

        return $this->queryProducts($categoryIds)->get()->map(fn (Product $product) => $this->formatProduct($product))->unique('slug')->keyBy('slug')->all();
    }

    public function product(string $slug): ?array
    {
        $product = $this->queryProducts()->whereHas('urls', fn (Builder $query) => $query->where('slug', $slug))->first();

        return $product ? $this->formatProduct($product) : null;
    }

    public function variant(string $slug, ?string $size = null): ?ProductVariant
    {
        $product = Product::query()->whereHas('urls', fn (Builder $query) => $query->where('slug', $slug))->first();

        if (! $product) {
            return null;
        }

        return $product->variants()->when(
            $size,
            fn (Builder $query) => $query->whereHas('values', fn (Builder $values) => $values
                ->where('name->bg', $size)
                ->whereHas('option', fn (Builder $option) => $option->where('handle', 'size')))
        )->first();
    }

    public function productForSku(string $sku): ?array
    {
        $variant = ProductVariant::query()->with(['product.urls', 'prices'])->where('sku', $sku)->first();

        return $variant?->product ? $this->formatProduct($variant->product, $variant) : null;
    }

    private function queryProducts($categoryIds = null): Builder
    {
        return Product::query()->where('status', 'published')->with([
            'urls' => fn (Relation $query) => $query->where('default', true),
            'collections',
            'variants.values.option',
            'variants.prices' => fn (Relation $query) => $query->where('currency_id', function ($subquery) {
                $subquery->select('id')->from(config('lunar.database.table_prefix').'currencies')->where('default', true)->limit(1);
            }),
        ])->when($categoryIds, fn (Builder $query) => $query->whereHas('collections', fn (Builder $collections) => $collections->whereIn(config('lunar.database.table_prefix').'collections.id', $categoryIds)));
    }

    private function formatProduct(Product $product, ?ProductVariant $chosenVariant = null): array
    {
        $slug = $product->urls->first()?->slug ?? '';
        $variants = $product->variants;
        $variant = $chosenVariant ?? $variants->first();
        $price = $variant?->prices->first();
        $category = $product->collections->first();
        $rootCategory = $category?->ancestors?->first() ?? $category;
        $sizes = $variants->flatMap(fn (ProductVariant $item) => $item->values->map(fn ($value) => $value->option?->handle === 'size' ? $value->translate('name') : null))->filter()->unique()->values()->all();

        return [
            'name' => $product->translateAttribute('name'),
            'slug' => $slug,
            'category' => $category?->translateAttribute('name') ?? 'Каталог',
            'category_slug' => $rootCategory?->urls()->where('default', true)->value('slug') ?? '',
            'price' => ($price?->price?->value ?? 0) / 100,
            'old_price' => $price?->compare_price?->value ? $price->compare_price->value / 100 : null,
            'badge' => null,
            'image' => self::IMAGES[$slug] ?? null,
            'media_url' => $product->getFirstMediaUrl(config('lunar.media.collection')),
            'description' => $product->translateAttribute('description') ?? '',
            'colors' => [],
            'sizes' => $sizes,
            'sku' => $variant?->sku ?? '',
            'stock' => $variant?->stock ?? 0,
        ];
    }
}
