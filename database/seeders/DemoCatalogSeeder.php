<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\Channel;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductType;
use Lunar\Models\TaxClass;
use Lunar\Models\Url;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $language = Language::where('code', 'bg')->firstOrFail();
        $currency = Currency::where('code', 'EUR')->firstOrFail();
        $channel = Channel::where('default', true)->firstOrFail();
        $group = CollectionGroup::where('handle', 'main')->firstOrFail();
        $taxClass = TaxClass::where('default', true)->firstOrFail();
        $type = ProductType::firstOrCreate(['name' => 'Stock']);
        $type->mappedAttributes()->sync(
            Attribute::whereIn('handle', ['name', 'description'])
                ->where('attribute_type', 'product')->pluck('id')
        );

        $categories = [
            'obuvki' => ['Обувки', ['maratonki' => 'Маратонки', 'ezhednevni-obuvki' => 'Ежедневни обувки']],
            'drehi' => ['Дрехи', ['teniski' => 'Тениски', 'yaketa' => 'Якета']],
            'instrumenti' => ['Инструменти', ['elektroinstrumenti' => 'Електроинструменти']],
        ];
        $collections = [];

        foreach ($categories as $slug => [$name, $children]) {
            $parent = $this->collection($group->id, $language->id, $slug, $name);
            $collections[$slug] = $parent;

            foreach ($children as $childSlug => $childName) {
                $child = $this->collection($group->id, $language->id, $childSlug, $childName);
                if ($child->parent_id !== $parent->id) {
                    $parent->appendNode($child);
                }
                $collections[$childSlug] = $child;
            }
        }

        $this->product($type, $language->id, $currency->id, $channel, $taxClass, $collections, [
            'slug' => 'urban-runner', 'name' => 'Urban Runner', 'category' => 'maratonki',
            'description' => 'Леки ежедневни маратонки с мека стелка и дишаща горна част.',
            'sku' => 'URB-RUN', 'price' => 66.42, 'compare' => 81.76, 'image' => 'photo-1542291026-7eec264c27ff',
            'sizes' => ['38', '39', '40', '41', '42', '43', '44'], 'stock' => 8,
        ]);
        $this->product($type, $language->id, $currency->id, $channel, $taxClass, $collections, [
            'slug' => 'essential-tee', 'name' => 'Essential тениска', 'category' => 'teniski',
            'description' => 'Плътна памучна тениска със свободна кройка.',
            'sku' => 'ESS-TEE', 'price' => 20.40, 'image' => 'photo-1521572163474-6864f9cf17ab',
            'sizes' => ['XS', 'S', 'M', 'L', 'XL'], 'stock' => 15,
        ]);
        $this->product($type, $language->id, $currency->id, $channel, $taxClass, $collections, [
            'slug' => 'impact-drill', 'name' => 'Бормашина Impact 18V', 'category' => 'elektroinstrumenti',
            'description' => 'Компактна акумулаторна бормашина с куфар и зарядно устройство.',
            'sku' => 'IMP-18V', 'price' => 96.63, 'image' => 'photo-1504148455328-c376907d081c',
            'sizes' => [], 'stock' => 5,
        ]);
        $this->product($type, $language->id, $currency->id, $channel, $taxClass, $collections, [
            'slug' => 'weekend-pack', 'name' => 'Weekend комплект', 'category' => 'yaketa',
            'description' => 'Подбрана комбинация за свободното време с удобна кройка.',
            'sku' => 'WKD-PACK', 'price' => 38.30, 'compare' => 45.97, 'image' => 'photo-1523381210434-271e8be1f52b',
            'sizes' => [], 'stock' => 12,
        ]);
    }

    private function collection(int $groupId, int $languageId, string $slug, string $name): LunarCollection
    {
        $url = Url::query()
            ->where('language_id', $languageId)
            ->where('element_type', LunarCollection::morphName())
            ->where('slug', $slug)
            ->oldest('id')
            ->first();
        $collection = $url?->element;

        if (! $collection) {
            $collection = LunarCollection::create([
                'collection_group_id' => $groupId,
                'attribute_data' => collect(['name' => new TranslatedText(['bg' => $name])]),
            ]);
        } else {
            $collection->update([
                'collection_group_id' => $groupId,
                'attribute_data' => collect(['name' => new TranslatedText(['bg' => $name])]),
            ]);
        }

        Url::updateOrCreate(
            ['language_id' => $languageId, 'element_type' => LunarCollection::morphName(), 'element_id' => $collection->id],
            ['slug' => $slug, 'default' => true],
        );

        return $collection;
    }

    private function product(ProductType $type, int $languageId, int $currencyId, Channel $channel, TaxClass $taxClass, array $collections, array $data): void
    {
        $url = Url::query()
            ->where('language_id', $languageId)
            ->where('element_type', Product::morphName())
            ->where('slug', $data['slug'])
            ->oldest('id')
            ->first();
        $attributes = [
            'product_type_id' => $type->id,
            'status' => 'published',
            'attribute_data' => collect([
                'name' => new TranslatedText(['bg' => $data['name']]),
                'description' => new TranslatedText(['bg' => $data['description']]),
            ]),
        ];
        $product = $url?->element;

        if ($product) {
            $product->update($attributes);
        } else {
            $product = Product::create($attributes);
        }
        $product->channels()->syncWithoutDetaching([$channel->id => ['enabled' => true, 'starts_at' => now()]]);
        $product->collections()->syncWithoutDetaching([$collections[$data['category']]->id]);
        Url::updateOrCreate(
            ['language_id' => $languageId, 'element_type' => Product::morphName(), 'element_id' => $product->id],
            ['slug' => $data['slug'], 'default' => true],
        );

        $sizeOption = null;
        if ($data['sizes']) {
            $sizeOption = ProductOption::firstOrCreate(
                ['handle' => 'size'],
                ['name' => ['bg' => 'Размер'], 'label' => ['bg' => 'Размер'], 'shared' => false],
            );
            $product->productOptions()->syncWithoutDetaching([$sizeOption->id => ['position' => 1]]);
        }

        foreach ($data['sizes'] ?: [null] as $index => $size) {
            $sku = $data['sku'].($size ? '-'.$size : '');
            $variant = $product->variants()->firstOrCreate(
                ['sku' => $sku],
                ['tax_class_id' => $taxClass->id, 'stock' => $data['stock'], 'shippable' => true],
            );
            $variant->prices()->updateOrCreate(
                ['currency_id' => $currencyId, 'min_quantity' => 1, 'customer_group_id' => null],
                ['price' => (int) round($data['price'] * 100), 'compare_price' => isset($data['compare']) ? (int) round($data['compare'] * 100) : null],
            );

            if ($sizeOption && $size) {
                $value = ProductOptionValue::firstOrCreate(
                    ['product_option_id' => $sizeOption->id, 'name->bg' => $size],
                    ['name' => ['bg' => $size], 'position' => $index],
                );
                $variant->values()->syncWithoutDetaching([$value->id]);
            }
        }
    }

}
