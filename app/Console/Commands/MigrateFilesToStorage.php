<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MigrateFilesToStorage extends Command
{
    protected $signature = 'files:migrate-to-storage';

    protected $description = 'Copy uploaded files from /public to storage/app/public and create the storage symlink';

    private array $uploadPaths = [
        'backend/images/admin/profile',
        'backend/images/car-models',
        'backend/images/currency-flag',
        'backend/images/web-settings/image-assets',
        'backend/images/seo',
        'backend/images/app',
        'backend/images/payment-gateways',
        'backend/images/extensions',
        'backend/images/default',
        'frontend/user',
        'frontend/images/site-section',
        'frontend/images/support-ticket/attachment',
        'backend/files/kyc-files',
        'backend/files/junk-files',
        'frontend/user-national-id',
        'frontend/user-driving-license',
    ];

    public function handle(): int
    {
        $this->info('Creating storage symlink...');
        Artisan::call('storage:link', ['--force' => true]);
        $this->line(trim(Artisan::output()));
        $this->newLine();

        $this->info('Migrating files from /public to storage/app/public...');
        $this->newLine();

        $moved = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->uploadPaths as $path) {
            $source = public_path($path);
            $destination = storage_path('app/public/' . $path);

            if (!File::isDirectory($source)) {
                $this->line("  <comment>No source dir:</comment> {$path}");
                continue;
            }

            if (!File::isDirectory($destination)) {
                File::makeDirectory($destination, 0755, true);
            }

            foreach (File::allFiles($source) as $file) {
                $relative = $file->getRelativePathname();
                $destFile = $destination . DIRECTORY_SEPARATOR . $relative;

                if (File::exists($destFile)) {
                    $skipped++;
                    continue;
                }

                $destDir = dirname($destFile);
                if (!File::isDirectory($destDir)) {
                    File::makeDirectory($destDir, 0755, true);
                }

                try {
                    File::copy($file->getRealPath(), $destFile);
                    $moved++;
                } catch (\Exception $e) {
                    $this->error("  Failed: {$relative} — " . $e->getMessage());
                    $errors++;
                }
            }

            $this->line("  <info>Processed:</info> {$path}");
        }

        $this->newLine();
        $this->info("Done: {$moved} moved, {$skipped} skipped, {$errors} errors.");
        $this->newLine();
        $this->warn('Original files in /public have NOT been deleted. Verify everything works, then remove them manually.');

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
