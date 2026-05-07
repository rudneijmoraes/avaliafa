<?php

namespace Database\Seeders;

use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@avalia-fa.local')],
            [
                'client_system_id' => null,
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin AvaliaFA'),
                'cpf' => env('SUPER_ADMIN_CPF', '00000000000'),
                'role' => 'super_admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Admin@2026!')),
                'active' => true,
            ]
        );

        $systems = ClientSystem::all();

        foreach ($systems as $system) {
            $slug = $system->slug;

            User::updateOrCreate(
                [
                    'email' => "admin@{$slug}.avalia-fa.local",
                    'client_system_id' => $system->id,
                ],
                [
                    'name' => "Admin {$system->name}",
                    'role' => 'admin',
                    'password' => Hash::make(env('ADMIN_PASSWORD', 'Admin@2026!')),
                    'active' => true,
                ]
            );
        }

        $this->command->info('Admin users seeded successfully.');
    }
}
