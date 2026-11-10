<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo catalogue for testing only: php artisan db:seed --class=DemoSeeder
 * Do NOT run on the live system.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $main = Branch::firstOrCreate(['code' => 'HS'], ['name' => 'Main Branch', 'address' => 'No. 1, Main Street', 'phone' => '011 234 5678']);
        Branch::firstOrCreate(['code' => 'CB'], ['name' => 'City Branch', 'address' => 'Colombo Road', 'phone' => '011 765 4321']);

        $admin = User::firstOrCreate(['email' => 'admin@pos.local'], [
            'name' => 'Admin', 'password' => 'admin123', 'role' => 'admin', 'branch_id' => $main->id,
        ]);
        User::firstOrCreate(['email' => 'cashier@pos.local'], [
            'name' => 'Cashier', 'password' => 'cashier123', 'role' => 'cashier', 'branch_id' => $main->id,
        ]);

        Setting::store([
            'store_name' => 'Hardware Store',
            'store_address' => 'No. 1, Main Street',
            'store_phone' => '011 234 5678',
            'tax_rate' => '15',
            'tax_label' => 'VAT',
        ]);

        $cats = [];
        foreach ([
            ['Building', 'fa-trowel-bricks'], ['Plumbing', 'fa-faucet-drip'], ['Electrical', 'fa-bolt'],
            ['Tools', 'fa-screwdriver-wrench'], ['Paints', 'fa-paint-roller'], ['Others', 'fa-ellipsis'],
        ] as $i => [$name, $icon]) {
            $cats[$name] = Category::firstOrCreate(['name' => $name], ['icon' => $icon, 'sort_order' => $i])->id;
        }

        $items = [
            ['CEM-50', '4791234500011', 'Cement (50kg)', 'Building', 'bag', 3900, 4250, 120],
            ['STR-12', '4791234500028', 'Steel Rod 12mm', 'Building', 'pcs', 2700, 2950, 85],
            ['SND-01', '4791234500035', 'River Sand (cube)', 'Building', 'cube', 16000, 18500, 12],
            ['BRK-01', '4791234500042', 'Clay Brick', 'Building', 'pcs', 22, 28, 5000],
            ['PVC-3', '4791234500059', 'PVC Pipe 3"', 'Plumbing', 'pcs', 1250, 1450, 60],
            ['PVE-3', '4791234500066', 'PVC Elbow 3"', 'Plumbing', 'pcs', 95, 120, 200],
            ['TAP-01', '4791234500073', 'Bib Tap 1/2"', 'Plumbing', 'pcs', 750, 950, 45],
            ['GLU-01', '4791234500080', 'PVC Solvent Cement 100ml', 'Plumbing', 'pcs', 290, 380, 70],
            ['LED-12', '4791234500097', 'LED Bulb 12W', 'Electrical', 'pcs', 690, 850, 150],
            ['WIR-25', '4791234500103', 'Wire 2.5mm (100m)', 'Electrical', 'roll', 11800, 13500, 20],
            ['SWT-01', '4791234500110', 'Switch 1 Gang', 'Electrical', 'pcs', 240, 320, 120],
            ['SKT-13', '4791234500127', 'Socket 13A', 'Electrical', 'pcs', 420, 560, 90],
            ['HAM-16', '4791234500134', 'Hammer 16oz', 'Tools', 'pcs', 980, 1250, 40],
            ['TAP-5M', '4791234500141', 'Measuring Tape 5m', 'Tools', 'pcs', 760, 980, 75],
            ['SCR-2', '4791234500158', 'Screw 2" (100pcs)', 'Tools', 'box', 480, 650, 300],
            ['DRL-01', '4791234500165', 'Drill Bit Set', 'Tools', 'set', 1900, 2450, 18],
            ['PNT-5L', '4791234500172', 'Paint (5L)', 'Paints', 'can', 7600, 8950, 35],
            ['PNT-1L', '4791234500189', 'Paint (1L)', 'Paints', 'can', 1700, 2100, 60],
            ['BRS-2', '4791234500196', 'Paint Brush 2"', 'Paints', 'pcs', 180, 250, 110],
            ['WPL-8', '4791234500202', 'Wall Plug 8mm', 'Others', 'pcs', 200, 280, 500],
            ['GLV-01', '4791234500219', 'Safety Gloves', 'Others', 'pair', 320, 450, 80],
        ];

        foreach ($items as [$code, $barcode, $name, $cat, $unit, $cost, $price, $qty]) {
            $p = Product::firstOrCreate(['code' => $code], [
                'barcode' => $barcode, 'name' => $name, 'category_id' => $cats[$cat], 'unit' => $unit,
                'cost_price' => $cost, 'sell_price' => $price, 'reorder_level' => 10,
            ]);
            if ($p->wasRecentlyCreated) {
                DB::transaction(fn () => StockService::apply($main->id, [$p->id => $qty], 'opening', 'product', $p->id, $admin->id, 'Opening stock'));
            }
        }

        Customer::firstOrCreate(['phone' => '0771234567'], ['name' => 'Nimal Perera', 'credit_limit' => 100000]);
        Customer::firstOrCreate(['phone' => '0719876543'], ['name' => 'Kamal Builders', 'credit_limit' => 500000]);
        Supplier::firstOrCreate(['name' => 'Sanka Cement Distributors'], ['contact_person' => 'Ruwan', 'phone' => '0112345111']);
        Supplier::firstOrCreate(['name' => 'Lanka Electricals'], ['contact_person' => 'Saman', 'phone' => '0112345222']);
    }
}
