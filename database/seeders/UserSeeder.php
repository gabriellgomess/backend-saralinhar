<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Gabriel Gomes',
            'email' => 'gabriel.gomes@outlook.com',
            'password' => Hash::make('10203040'),
        ]);

        User::create([
            'name' => 'Sara Linhar',
            'email' => 'sara@saralinhar.com.br',
            'password' => Hash::make('Isaenuna'),
        ]);
    }
}
