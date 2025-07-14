<?php

namespace Artisan\Downloader;

use PharData;
use RecursiveIteratorIterator;
use RuntimeException;
use InvalidArgumentException;

class GeoLite2Downloader
{
    private string $licenseKey;
    private string $editionId;
    private string $destinationPath;
    private string $tmpDir;

    public function __construct(array $config)
    {
        if (empty($config['license_key'])) {
            throw new InvalidArgumentException('Missing "license_key" in config.');
        }
        if (empty($config['mmdb'])) {
            throw new InvalidArgumentException('Missing "mmdb" path in config.');
        }

        $this->licenseKey = $config['license_key'];
        $this->editionId = $config['edition_id'] ?? 'GeoLite2-City';
        $this->destinationPath = $config['mmdb'];
        $this->tmpDir = sys_get_temp_dir() . '/geoip' ;
    }

    public function download(?callable $logger = null): string
    {
        $this->log("Starting GeoLite2 download...", $logger);

        $this->cleanup();
        $destDir = dirname($this->destinationPath);
        $this->prepareDirectories($destDir, $logger);

        $url = $this->buildUrl();
        $compressedFile = $this->tmpDir . '/GeoLite2.tar.gz';
        $tarFile = $this->tmpDir . '/GeoLite2.tar';

        if (file_exists($tarFile)) {
            unlink($tarFile);
        }

        $this->log("Downloading archive...", $logger);
        file_put_contents($compressedFile, fopen($url, 'r'));

        $this->log("Decompressing...", $logger);
        $phar = new PharData($compressedFile);
        $phar->decompress();

        $tar = new PharData($tarFile);
        $mmdbPath = $this->findMmdbPath($tar);

        $this->log("Extracting database...", $logger);
        $tar->extractTo($this->tmpDir, $mmdbPath, true);

        $sourceFile = $this->tmpDir . DIRECTORY_SEPARATOR . $mmdbPath;
        $this->log("Moving file to: {$this->destinationPath}", $logger);
        rename($sourceFile, $this->destinationPath);

        $this->cleanup();

        $this->log("Download complete.", $logger);
        return $this->destinationPath;
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
        throw new RuntimeException("MMDB file not found in archive.");
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
