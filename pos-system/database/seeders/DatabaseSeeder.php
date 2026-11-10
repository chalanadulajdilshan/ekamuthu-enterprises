<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Production bootstrap: safe to run more than once, creates nothing if it already exists.
 *   - one branch (rename it / add more in Settings)
 *   - the hardware category tabs
 *   - the first admin login (POS_ADMIN_EMAIL / POS_ADMIN_PASSWORD via config/pos.php,
 *     otherwise a random password that is printed once)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->orderBy('id')->first()
            ?? Branch::create(['name' => 'Main Branch', 'code' => 'MAIN']);

        if (! User::query()->where('role', 'admin')->exists()) {
            // config(), not env(): env() returns null once `php artisan optimize` caches config.
            $email = config('pos.admin_email');
            $configured = (string) config('pos.admin_password');
            $password = $configured !== '' ? $configured : Str::password(12, symbols: false);

            User::create([
                'name' => 'Admin', 'email' => $email, 'password' => $password,
                'role' => 'admin', 'branch_id' => $branch->id,
            ]);

            // Never echo a configured password (deploy logs of a public repo are public).
            $this->command?->warn('Admin login created -> '.$email.' / '.($configured !== '' ? '(POS_ADMIN_PASSWORD)' : $password));
            $this->command?->warn('Change it in Settings > Users after the first login.');
        }

        if (! Category::query()->exists()) {
            foreach ([
                ['Building', 'fa-trowel-bricks'], ['Plumbing', 'fa-faucet-drip'], ['Electrical', 'fa-bolt'],
                ['Tools', 'fa-screwdriver-wrench'], ['Paints', 'fa-paint-roller'], ['Others', 'fa-ellipsis'],
            ] as $i => [$name, $icon]) {
                Category::create(['name' => $name, 'icon' => $icon, 'sort_order' => $i]);
            }
        }

        // Only fills keys that were never saved, so admin changes are kept.
        $missing = array_diff_key(['tax_label' => 'VAT'], Setting::query()->pluck('value', 'key')->all());
        if ($missing) {
            Setting::store($missing);
        }
    }
}
