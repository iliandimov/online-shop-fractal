<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\TaxZone;

class StoreDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        Currency::query()->update(['default' => false]);

        Currency::updateOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'exchange_rate' => 1,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ],
        );

        Language::updateOrCreate(
            ['code' => 'bg'],
            ['name' => 'Български', 'default' => true],
        );

        TaxZone::query()->where('default', true)->update([
            'price_display' => 'tax_inclusive',
        ]);
    }
}
