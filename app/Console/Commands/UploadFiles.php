<?php

namespace App\Console\Commands;

use App\Services\UploadService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Exception\RuntimeException;

class UploadFiles extends Command
{
    protected $signature = 's3:upload {folder=upload}';
    protected $description = 'Upload files from a folder to S3 bucket';

    public function handle(UploadService $service): void
    {
        $folder = $this->argument('folder');

        $path = Storage::disk('local')->path($folder);

        $storePath = Carbon::now()->toDateTimeString();

        $storePath = Str::replace(['.', '/', ':'], '-', $storePath);
        $storePath = Str::replace(' ', '_', $storePath);

        if (!File::exists($path)) {
            throw new RuntimeException('Directory does not exist.');
        }

        $contentSize = $service->calculateFolderSize($path);

        if ($contentSize == 0) {
            throw new RuntimeException('Directory is empty.');
        }

        $sizeThreshold = config('app.file_size_threshold') * 1024 * 1024;

        if ($contentSize < $sizeThreshold) {
            $service->uploadFiles($path, $storePath);
        } else {
            $service->asyncUploadFiles($path, $sizeThreshold, $storePath);
        }

        $this->info('File upload completed.');

        foreach (Storage::disk('s3')->allFiles() as $path) {
            $this->line($path);
        };
    }
}
