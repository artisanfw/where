<?php

namespace Artisan\Services;

use Artisan\Entities\Location;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;

class Where
{
    private static Reader $mmdb;

    /**
     * @throws InvalidDatabaseException
     */
    public static function load(array $config): void
    {
        if (empty($config['mmdb'])) {
            throw new \InvalidArgumentException('Missing "mmdb" path in config.');
        }
        self::$mmdb = new Reader($config['mmdb']);
    }

    public static function is(string $ip): ?Location
    {
        try {
            $city = self::$mmdb->city($ip);
            return new Location(
                $city->continent->name,
                $city->country->name,
                $city->country->isoCode,
                $city->mostSpecificSubdivision->name,
                $city->city->name,
                $city->location->latitude,
                $city->location->longitude,
                $city->location->timeZone
            );

        } catch (\Throwable $t) {
            return null;
        }
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public static function raw(string $ip): City
    {
        return self::$mmdb->city($ip);
    }

}