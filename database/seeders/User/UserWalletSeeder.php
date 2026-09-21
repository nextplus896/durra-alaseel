<?php

namespace Database\Seeders\User;

use App\Models\UserWallet;
use Illuminate\Database\Seeder;

class UserWalletSeeder extends Seeder
{
    public function run()
    {
        // Update all existing wallets to 10000 SAR
        UserWallet::query()->update(['balance' => '10000.00000000']);

        // Create wallets for users who don't have one
        $usersWithoutWallet = \App\Models\User::whereNotIn('id', function ($query) {
            $query->select('user_id')->from('user_wallets');
        })->pluck('id');

        $wallets = [];
        foreach ($usersWithoutWallet as $userId) {
            $wallets[] = [
                'user_id'     => $userId,
                'currency_id' => 1,
                'balance'     => '10000.00000000',
                'status'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (count($wallets) > 0) {
            UserWallet::insert($wallets);
        }

        $this->command->info('All user wallets seeded with 10,000 SAR.');
    }
}
