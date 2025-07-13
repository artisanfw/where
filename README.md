# where

A standalone service for IP geolocation using the MaxMind GeoLite2-City database.  
This package allows you to query an IP address for location data, and optionally provides a downloader utility to fetch the latest MaxMind database.

## Installation
Require the package and its dependencies via Composer:

```bash
composer require artisanfw/where
```

## Loading the service
To use the Where service, you must first load the `.mmdb` database:

```php
use Artisan\Services\Where;

$config = [
'mmdb' => '/path/to/GeoLite2-City.mmdb',
];

Where::load($config);
```

## Usage
```php
$info = Where::is('8.8.8.8'); // returns Location object or null

if ($info) {
    $info->getContinent();
    $info->getCountry();
    $info->getRegion();
    $info->getCity();
    $info->getLatitude();
    $info->getLongitude();
    $info->getTimeZone();
}
```

## Downloading the Database
The `GeoLite2Downloader` class provides a convenient way to fetch and extract the latest MaxMind GeoLite2-City database using a license key.

### Example (Standalone CLI)
```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Artisan\Downloader\GeoLite2Downloader;

$config = [
    'license_key' => 'your_key_here',
    'mmdb' => '/path/to/geodb/GeoLite2-City.mmdb',
];

$downloader = new GeoLite2Downloader($config);
try {
    $downloader->download();
    echo "✔ Database downloaded successfully.\n";
} catch (Throwable $e) {
    echo "✖ Error: " . $e->getMessage() . "\n";
}
```
### Make the script executable:

```bash
chmod +x geodb_update.php
```
And run it manually or via crontab:

```cron
0 3 * * * /path/to/geodb_update.php >> /var/log/geodb_update.log 2>&1
```

### Example (Symfony Console)
You can wrap the downloader in a Symfony Command:
```php
// src/Command/GeoLiteDownloadCommand.php

namespace App\Command;

use Artisan\Downloader\GeoLite2Downloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'geodb:download', description: 'Download GeoLite2 database')]
class GeoLiteDownloadCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('license_key', InputArgument::REQUIRED, 'Your MaxMind license key')
            ->addArgument('mmdb_path', InputArgument::REQUIRED, 'Full destination path of the .mmdb file (e.g. /path/to/GeoLite2-City.mmdb)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = [
            'license_key' => $input->getArgument('license_key'),
            'mmdb' => $input->getArgument('mmdb_path'),
        ];

        try {
            $downloader = new GeoLite2Downloader($config);
            $downloader->download(fn($msg) => $output->writeln($msg));
            $output->writeln('<info>GeoLite2 database downloaded successfully.</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
```

## License
This package is licensed under the MIT License.

The MaxMind GeoLite2 data is subject to [MaxMind's EULA](https://www.maxmind.com/en/geolite2/eula).

