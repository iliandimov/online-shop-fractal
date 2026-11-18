<?php

namespace App\Http\Controllers;

use App\Services\StoreCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Lunar\DataTypes\Price as LunarPrice;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\CartSession;
use Lunar\Facades\Payments;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Country;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class StorefrontController extends Controller
{
    public function index(StoreCatalog $catalog): View
    {
        $products = $catalog->products();

        return view('store.home', [
            'categories' => $catalog->categories(),
            'products' => $products,
            'featured' => array_slice($products, 0, 3, true),
        ]);
    }

    public function category(string $slug, StoreCatalog $catalog): View
    {
        $categories = $catalog->categories();
        $category = collect($categories)->firstWhere('slug', $slug);
        abort_unless($category, 404);

        return view('store.category', [
            'category' => $category,
            'products' => $catalog->products($slug),
            'categories' => $categories,
        ]);
    }

    public function product(string $slug, StoreCatalog $catalog): View
    {
        $product = $catalog->product($slug);
        abort_unless($product, 404);

        return view('store.product', [
            'product' => $product,
            'related' => collect($catalog->products())->except($slug)->take(3)->all(),
            'categories' => $catalog->categories(),
        ]);
    }

    public function cart(Request $request, StoreCatalog $catalog): View
    {
        return view('store.cart', $this->cartData($request, $catalog));
    }

    public function addToCart(Request $request, StoreCatalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'size' => ['nullable', 'string', 'max:12'],
            'color' => ['nullable', 'string', 'max:30'],
        ]);
        $product = $catalog->product($data['slug']) ?? abort(404);
        abort_if($product['sizes'] && empty($data['size']), 422, 'Избери размер.');
        $variant = $catalog->variant($data['slug'], $data['size'] ?? null);
        abort_unless($variant, 422, 'Избраният вариант не е наличен.');

        $cart = $this->currentCart($request, create: true);
        $inCart = $cart->lines()
            ->where('purchasable_type', $variant->getMorphClass())
            ->where('purchasable_id', $variant->getKey())
            ->sum('quantity');
        abort_if($inCart + $data['quantity'] > $variant->stock, 422, 'Няма достатъчна наличност.');

        $cart->add($variant, $data['quantity'], array_filter([
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        return redirect()->route('cart')->with('success', 'Добавихме продукта в количката.');
    }

    public function updateCart(Request $request, int $lineId): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']]);
        $cart = $this->currentCart($request) ?? abort(404);
        $line = $cart->lines()->with('purchasable')->findOrFail($lineId);
        $variant = $line->purchasable;
        abort_unless($variant instanceof ProductVariant, 404);

        $otherQuantity = $cart->lines()
            ->where('purchasable_type', $line->purchasable_type)
            ->where('purchasable_id', $line->purchasable_id)
            ->where('id', '<>', $line->getKey())
            ->sum('quantity');
        abort_if($otherQuantity + $data['quantity'] > $variant->stock, 422, 'Няма достатъчна наличност.');
        $cart->updateLine($line->id, $data['quantity']);

        return redirect()->route('cart');
    }

    public function removeFromCart(Request $request, int $lineId): RedirectResponse
    {
        $cart = $this->currentCart($request) ?? abort(404);
        abort_unless($cart->lines()->whereKey($lineId)->exists(), 404);
        $cart->remove($lineId);

        return redirect()->route('cart');
    }

    public function checkout(Request $request, StoreCatalog $catalog): View|RedirectResponse
    {
        if (! $this->currentCart($request)?->lines()->exists()) {
            return redirect()->route('cart');
        }

        return view('store.checkout', $this->cartData($request, $catalog));
    }

    public function placeOrder(Request $request, StoreCatalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:240'],
            'courier' => ['required', 'in:speedy,econt,boxnow,pigeon'],
        ]);
        $currentCart = $this->currentCart($request) ?? abort(422, 'Количката е празна.');

        $order = DB::transaction(function () use ($data, $currentCart) {
            $cart = CartSession::manager();
            abort_unless($cart && $cart->is($currentCart), 422, 'Не успяхме да заредим количката.');
            abort_if($cart->lines()->doesntExist(), 422, 'Количката е празна.');
            $cart->calculate(force: true);
            $shippingPrice = $cart->subTotal->value >= 7500 ? 0 : 353;
            $country = Country::where('iso2', 'BG')->firstOrFail();
            [$firstName, $lastName] = array_pad(explode(' ', trim($data['name']), 2), 2, '');
            $courierNames = [
                'speedy' => 'Speedy',
                'econt' => 'Econt',
                'boxnow' => 'BOX NOW',
                'pigeon' => 'Pigeon Express',
            ];

            $cart->setShippingAddress([
                'country_id' => $country->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'city' => $data['city'],
                'line_one' => $data['address'],
                'contact_email' => $data['email'],
                'contact_phone' => $data['phone'],
                'meta' => ['courier' => $data['courier']],
            ]);

            $shippingOption = new ShippingOption(
                name: $courierNames[$data['courier']],
                description: 'Доставка с наложен платеж',
                identifier: 'store-'.$data['courier'],
                price: new LunarPrice($shippingPrice, $cart->currency),
                taxClass: TaxClass::getDefault(),
                option: 'Наложен платеж',
                meta: ['courier' => $data['courier'], 'payment_method' => 'cash-on-delivery'],
            );
            ShippingManifest::addOption($shippingOption);
            $cart->setShippingOption($shippingOption);
            $cart->recalculate();

            $response = Payments::driver('cash-in-hand')
                ->cart($cart)
                ->withData(['meta' => [
                    'payment_method' => 'cash-on-delivery',
                    'courier' => $data['courier'],
                ]])
                ->authorize();

            abort_unless($response?->success, 422, 'Поръчката не можа да бъде запазена.');

            foreach ($cart->lines()->with('purchasable')->get() as $line) {
                $variant = $line->purchasable;
                abort_unless($variant instanceof ProductVariant, 422, 'Невалиден продукт в количката.');
                $updated = ProductVariant::query()
                    ->whereKey($variant->getKey())
                    ->where('stock', '>=', $line->quantity)
                    ->decrement('stock', (int) $line->quantity);
                abort_unless($updated === 1, 422, 'Наличността за '.$variant->sku.' вече не е достатъчна.');
            }

            return \Lunar\Models\Order::findOrFail($response->orderId);
        });

        CartSession::forget(false);
        $request->session()->put('last_order', [
            'number' => $order->reference,
            'total' => $order->total->value / 100,
        ]);

        return redirect()->route('order.complete');
    }

    public function orderComplete(Request $request, StoreCatalog $catalog): View|RedirectResponse
    {
        $order = $request->session()->get('last_order');

        return $order
            ? view('store.order-complete', ['order' => $order, 'categories' => $catalog->categories()])
            : redirect()->route('home');
    }

    private function cartData(Request $request, StoreCatalog $catalog): array
    {
        $cart = $this->currentCart($request);
        $items = ($cart?->lines()->with('purchasable')->get() ?? collect())->map(function (CartLine $line) use ($catalog) {
            $variant = $line->purchasable;
            abort_unless($variant instanceof ProductVariant, 404);
            $sku = $variant->sku;
            $product = $catalog->productForSku($sku) ?? abort(404);
            $meta = (array) $line->meta;

            return [
                'line_id' => $line->id,
                'sku' => $sku,
                'quantity' => $line->quantity,
                'size' => $meta['size'] ?? null,
                'color' => $meta['color'] ?? null,
                'product' => $product,
                'line_total' => $product['price'] * $line->quantity,
            ];
        });
        $subtotal = $items->sum('line_total');
        $shipping = $items->isEmpty() || $subtotal >= 75 ? 0 : 3.53;

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'cartCount' => $items->sum('quantity'),
            'categories' => $catalog->categories(),
        ];
    }

    private function currentCart(Request $request, bool $create = false): ?\Lunar\Models\Cart
    {
        $cart = CartSession::current(calculate: false);
        $legacyLines = $request->session()->get('cart', []);

        if ($legacyLines !== []) {
            if (! $cart || $cart->lines()->doesntExist()) {
                $cart ??= CartSession::manager();

                foreach ($legacyLines as $sku => $legacyLine) {
                    $variant = ProductVariant::query()->where('sku', $sku)->first();
                    $quantity = min((int) ($legacyLine['quantity'] ?? 0), (int) ($variant?->stock ?? 0));

                    if (! $variant || $quantity < 1) {
                        continue;
                    }

                    $cart->add($variant, $quantity, array_filter([
                        'size' => $legacyLine['size'] ?? null,
                        'color' => $legacyLine['color'] ?? null,
                    ], fn ($value) => $value !== null && $value !== ''), refresh: false);
                }

                $cart->refresh()->recalculate();
            }

            $request->session()->forget('cart');
        }

        return $cart ?? ($create ? CartSession::manager() : null);
    }
}
