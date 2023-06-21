<?php

namespace App\Services;

use App\Jobs\UploadFilesJob;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class UploadService
{
    public function calculateFolderSize(string $folder): int
    {
        $totalSize = 0;
        foreach (File::allFiles($folder) as $file) {
            $totalSize += $file->getSize();
        }

        return $totalSize;
    }

    public function uploadFiles(string $folder, string $storePath): void
    {
        foreach (File::allFiles($folder) as $file) {
            Storage::disk('s3')->putFileAs($storePath, $file, $file->getFilename());
        }
    }

    public function asyncUploadFiles(string $sourceFolder, int $archiveSize, string $storePath): void
    {
        $files = File::allFiles($sourceFolder);
        $archiveNumber = 1;
        $currentArchiveSize = 0;
        $archiveFiles = [];

        foreach ($files as $file) {
            if (($currentArchiveSize + $file->getSize()) > $archiveSize) {
                UploadFilesJob::dispatch($archiveFiles, $archiveNumber, $storePath)->onConnection('sqs');

                $currentArchiveSize = 0;
                $archiveFiles = [];
                $archiveNumber++;
            }
            $currentArchiveSize += $file->getSize();
            $archiveFiles[] = $file->getPathname();
        }

        if (!empty($archiveFiles)) {
            UploadFilesJob::dispatch($archiveFiles, $archiveNumber, $storePath)->onConnection('sqs');
        }
    }

    public function archiveFilesAndUpload(array $filePaths, int $archiveNumber, string $storePath): void
    {
        $archiveName = 'archive_' . $archiveNumber . '.zip';
        $archivePathName = Storage::disk('local')->path($archiveName);

        $zip = new ZipArchive();

        $zip->open($archivePathName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($filePaths as $filePath) {
            $zip->addFile($filePath);
        }

        $zip->close();

        Storage::disk('s3')->putFileAs($storePath, $archivePathName, $archiveName);

        unlink($archivePathName);
    }
}
