<?php

namespace App\Console\Commands;

use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\User;
use Illuminate\Console\Command;

class PusherDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pusher:debug {user_id?} {--test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug Pusher Beams push notification configuration and test notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Pusher Beams Debug Information');
        $this->info('================================');
        $this->newLine();

        // Check basic settings
        $settings = BasicSettings::first();
        
        $this->info('📋 Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Push Notifications Enabled', $settings->push_notification ? '✅ Yes' : '❌ No'],
                ['Instance ID', $settings->push_notification_config->instance_id ?? 'NOT SET'],
                ['Primary Key', $settings->push_notification_config->primary_key ? '✅ Configured' : '❌ Not Configured'],
            ]
        );
        $this->newLine();

        // Test user ID
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("❌ User with ID {$userId} not found!");
                return 1;
            }

            $this->info("👤 User Information:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $user->id],
                    ['Name', $user->fullname],
                    ['Email', $user->email],
                    ['Mobile', $user->mobile ?? 'N/A'],
                ]
            );
            $this->newLine();

            // Generate publishable ID
            $helper = new PushNotificationHelper();
            $publishableId = $helper->make_publishable_id($user->id, 'user');
            
            $this->info("🔑 Publishable ID: {$publishableId}");
            $this->newLine();

            // Test notification if requested
            if ($this->option('test')) {
                if (!$settings->push_notification) {
                    $this->error('❌ Push notifications are disabled in settings!');
                    return 1;
                }

                $this->info('📤 Sending test notification...');
                
                try {
                    $result = $helper->prepare(
                        [$user->id],
                        [
                            'title' => 'Test Notification',
                            'desc' => "Hello {$user->fullname}! This is a test push notification from Pusher Debug Command.",
                            'user_type' => 'user',
                        ]
                    )->send();

                    $this->info('✅ Notification sent successfully!');
                    $this->info('📬 Publish ID: ' . ($result->publishId ?? 'N/A'));
                    $this->newLine();
                    $this->info('💡 Check the logs with: tail -f storage/logs/laravel.log | grep "Pusher:"');
                } catch (\Exception $e) {
                    $this->error('❌ Failed to send notification: ' . $e->getMessage());
                    return 1;
                }
            } else {
                $this->info('💡 Tip: Add --test flag to send a test notification to this user');
                $this->info('   Example: php artisan pusher:debug ' . $user->id . ' --test');
            }
        } else {
            // Show all users with their publishable IDs
            $this->info('👥 All Users and their Publishable IDs:');
            
            $users = User::select('id', 'firstname', 'lastname', 'email')
                ->orderBy('id')
                ->limit(20)
                ->get();

            $helper = new PushNotificationHelper();
            $tableData = [];
            
            foreach ($users as $user) {
                $publishableId = $helper->make_publishable_id($user->id, 'user');
                $tableData[] = [
                    $user->id,
                    $user->fullname,
                    $user->email,
                    $publishableId,
                ];
            }

            $this->table(
                ['ID', 'Name', 'Email', 'Publishable ID'],
                $tableData
            );
            
            $this->newLine();
            $this->info('💡 Tip: Use "php artisan pusher:debug {user_id}" to see details for a specific user');
            $this->info('   Example: php artisan pusher:debug 4');
        }

        $this->newLine();
        $this->info('📖 Useful Commands:');
        $this->line('   • View logs: tail -f storage/logs/laravel.log | grep "Pusher:"');
        $this->line('   • Test notification: php artisan pusher:debug 4 --test');
        $this->line('   • Check config: php artisan tinker --execute="dump(App\Models\Admin\BasicSettings::first()->push_notification_config)"');

        return 0;
    }
}
