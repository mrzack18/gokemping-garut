<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Membuat satu admin untuk setiap unit bisnis. Kedua admin hanya boleh
 * melihat data unit bisnisnya sendiri (BR-05).
 */
class AdminSeeder extends Seeder
{
    /**
     * Kredensial development. Wajib diganti sebelum production.
     */
    public const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $admins = [
            [
                'business' => 'gokemping',
                'name' => 'Admin GoKemping',
                'email' => 'admin@gokemping.test',
                'phone' => '081234567890',
            ],
            [
                'business' => 'sewa-sepeda-garut',
                'name' => 'Admin Sewa Sepeda Garut',
                'email' => 'admin@sewasepedagarut.test',
                'phone' => '089876543210',
            ],
        ];

        foreach ($admins as $admin) {
            $business = Business::where('slug', $admin['business'])->firstOrFail();

            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'business_id' => $business->id,
                    'name' => $admin['name'],
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'role' => 'admin',
                    'phone' => $admin['phone'],
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
