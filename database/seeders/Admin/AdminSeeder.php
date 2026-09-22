<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'firstname'     => "Super",
                'lastname'      => "Admin",
                'username'      => "superadmin",
                'email'         => "superadmin@dora-rent.com",
                'password'      => Hash::make("dorarent"),
                'created_at'    => now(),
                'status'        => true,
            ],
            [
                'firstname'     => "Sub",
                'lastname'      => "Admin",
                'username'      => "subadmin",
                'email'         => "subadmin@dora-rent.com",
                'password'      => Hash::make("dorarent"),
                'created_at'    => now(),
                'status'        => true,
            ],
            [
                'firstname'     => "Support",
                'lastname'      => "Admin",
                'username'      => "supportadmin",
                'email'         => "supportadmin@dora-rent.com",
                'password'      => Hash::make("dorarent"),
                'created_at'    => now(),
                'status'        => true,
            ],
            [
                'firstname'     => "Editor",
                'lastname'      => "Admin",
                'username'      => "editoradmin",
                'email'         => "editoradmin@dora-rent.com",
                'password'      => Hash::make("dorarent"),
                'created_at'    => now(),
                'status'        => true,
            ],
            [
                'firstname'     => "Moderator",
                'lastname'      => "Admin",
                'username'      => "moderatoradmin",
                'email'         => "moderatoradmin@dora-rent.com",
                'password'      => Hash::make("dorarent"),
                'created_at'    => now(),
                'status'        => true,
            ],
        ];

        Admin::insert($data);
    }
}
