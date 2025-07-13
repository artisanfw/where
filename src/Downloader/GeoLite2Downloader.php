<?php

namespace Artisan\Downloader;

use PharData;
use RecursiveIteratorIterator;

class GeoLite2Downloader
{
    private string $licenseKey;
    private string $editionId;
    private string $tmpDir;
    private string $filename;

    public function __construct(string $licenseKey, string $editionId = 'GeoLite2-City')
    {
        $this->licenseKey = $licenseKey;
        $this->editionId = $editionId;
        $this->tmpDir = sys_get_temp_dir() . '/geoip_' . uniqid();
        $this->filename = $editionId . '.mmdb';
    }

    public function download(string $destinationPath, ?callable $logger = null): string
    {
        $this->log("Starting GeoLite2 download...", $logger);

        $this->prepareDirectories($destinationPath, $logger);
        $url = $this->buildUrl();

        $compressedFile = $this->tmpDir . '/GeoLite2.tar.gz';
        $tarFile = $this->tmpDir . '/GeoLite2.tar';

        $this->log("Downloading ...", $logger);
        file_put_contents($compressedFile, fopen($url, 'r'));

        $this->log("Decompressing archive...", $logger);
        $phar = new PharData($compressedFile);
        $phar->decompress();

        $tar = new PharData($tarFile);
        $mmdbPath = $this->findMmdbPath($tar);

        $this->log("Extracting database file...", $logger);
        $tar->extractTo($this->tmpDir, $mmdbPath, true);

        $sourceFile = $this->tmpDir . DIRECTORY_SEPARATOR . $mmdbPath;
        $destinationFile = rtrim($destinationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->filename;

        $this->log("Moving file to: $destinationFile", $logger);
        rename($sourceFile, $destinationFile);

        $this->cleanup();

        $this->log("Download complete.", $logger);
        return $destinationFile;
    }

    private function buildUrl(): string
    {
        return sprintf(
            "https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz",
            $this->editionId,
            $this->licenseKey
        );
    }

    private function prepareDirectories(string $dest, ?callable $logger): void
    {
        $this->log("Creating temporary directory: {$this->tmpDir}", $logger);
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        if (!is_dir($dest)) {
            $this->log("Creating destination directory: $dest", $logger);
            mkdir($dest, 0755, true);
        }
    }

    private function findMmdbPath(PharData $tar): string
    {
        foreach (new RecursiveIteratorIterator($tar) as $file) {
            if (str_ends_with($file->getFilename(), '.mmdb')) {
                return str_replace("phar://{$tar->getPathname()}/", '', $file->getPathname());
            }
        }
        throw new \RuntimeException("MMDB file not found in archive.");
    }

    private function cleanup(): void
    {
        $this->deleteDir($this->tmpDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function log(string $message, ?callable $logger): void
    {
        if ($logger) {
            $logger($message);
        } else {
            echo "[GeoLite2] $message" . PHP_EOL;
        }
    }
}
