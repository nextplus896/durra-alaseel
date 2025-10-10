<?php
namespace Database\Seeders;
use Exception;
use Illuminate\Database\Seeder;
use App\Models\Admin\AppSettings;
use App\Models\Admin\BasicSettings;
use Database\Seeders\Admin\PaymentGatewaySeeder;
use Database\Seeders\Admin\SectionHasPageSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UpdateFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(BasicSettings::first()) {
            BasicSettings::first()->update([
                'web_version'       => "1.2.0",
            ]);
        }
        if(AppSettings::first()){
            AppSettings::first()->update(['version' => '1.2.0','vendor_version' => '1.2.0']);
        }

        $this->call([
            SectionHasPageSeeder::class,
            PaymentGatewaySeeder::class
        ]);



        try{
            update_project_localization_data();
        }catch(Exception $e) {
            // handle error
        }
    }
}
