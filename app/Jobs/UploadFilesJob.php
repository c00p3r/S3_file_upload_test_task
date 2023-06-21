<?php

namespace App\Jobs;

use App\Services\UploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $filePaths;
    protected int $archiveNumber;
    protected string $storePath;

    public function __construct(array $filePaths, int $archiveNumber, string $storePath)
    {
        $this->filePaths = $filePaths;
        $this->archiveNumber = $archiveNumber;
        $this->storePath = $storePath;
    }

    public function handle(UploadService $service): void
    {
        $service->archiveFilesAndUpload($this->filePaths, $this->archiveNumber, $this->storePath);
    }
}
