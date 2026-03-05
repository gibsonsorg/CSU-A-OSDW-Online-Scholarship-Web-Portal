<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          User::create([
            'name' => 'Admin',
            'email' => 'osdwaparri@csu.edu.ph',
            'password' => Hash::make('admin123'),
            'role' => '0',
        ]);
    }
}
