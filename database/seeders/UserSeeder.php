<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@psipos.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '+1 (555) 019-2831',
                'status' => 'active',
                'manager_pin' => '1234',
            ],
            [
                'name' => 'Store Manager',
                'username' => 'manager',
                'email' => 'manager@psipos.com',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'phone' => '+1 (555) 019-4822',
                'status' => 'active',
                'manager_pin' => '1234',
            ],
            [
                'name' => 'Front Desk Cashier',
                'username' => 'cashier',
                'email' => 'cashier@psipos.com',
                'password' => Hash::make('cashier123'),
                'role' => 'cashier',
                'phone' => '+1 (555) 019-9941',
                'status' => 'active',
            ],
            [
                'name' => 'Head Office Operations Manager',
                'username' => 'headoffice',
                'email' => 'headoffice@psipos.com',
                'password' => Hash::make('head123'),
                'role' => 'head_office_manager',
                'phone' => '+94 77 111 2233',
                'status' => 'active',
            ],
            [
                'name' => 'Inventory Controller',
                'username' => 'inventory',
                'email' => 'inventory@psipos.com',
                'password' => Hash::make('inv123'),
                'role' => 'inventory_staff',
                'phone' => '+94 77 444 5566',
                'status' => 'active',
            ],
            [
                'name' => 'Central Warehouse Manager',
                'username' => 'warehouse',
                'email' => 'warehouse@psipos.com',
                'password' => Hash::make('wh123'),
                'role' => 'warehouse_manager',
                'phone' => '+94 77 777 8899',
                'status' => 'active',
            ],
            [
                'name' => 'Financial Accountant',
                'username' => 'accountant',
                'email' => 'accountant@psipos.com',
                'password' => Hash::make('acc123'),
                'role' => 'accountant',
                'phone' => '+94 77 999 0011',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}

