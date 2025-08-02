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

    /**
     * ISO 3166-1 alpha-2
     *
     * @return string[]
     */
    public static function allISO2Countries(): array
    {
        return [
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AR',
            'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD',
            'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BM', 'BN', 'BO',
            'BR', 'BS', 'BT', 'BW', 'BY', 'BZ', 'CA', 'CD', 'CF',
            'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR',
            'CU', 'CV', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO',
            'DZ', 'EC', 'EE', 'EG', 'ER', 'ES', 'ET', 'FI', 'FJ',
            'FM', 'FR', 'GA', 'GB', 'GD', 'GE', 'GH', 'GI', 'GL',
            'GM', 'GN', 'GQ', 'GR', 'GT', 'GW', 'GY', 'HK', 'HN',
            'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IN', 'IQ', 'IR',
            'IS', 'IT', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI',
            'KM', 'KN', 'KP', 'KR', 'KW', 'KZ', 'LA', 'LB', 'LC',
            'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA',
            'MC', 'MD', 'ME', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN',
            'MR', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
            'NE', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NZ', 'OM',
            'PA', 'PE', 'PG', 'PH', 'PK', 'PL', 'PT', 'PW', 'PY',
            'QA', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD',
            'SE', 'SG', 'SI', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR',
            'SS', 'ST', 'SV', 'SY', 'SZ', 'TD', 'TG', 'TH', 'TJ',
            'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TZ', 'UA',
            'UG', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VN', 'VU',
            'WS', 'YE', 'ZA', 'ZM', 'ZW'
        ];
    }

    public static function validCountry(string $code): bool
    {
        return in_array(trim(strtoupper($code)), self::allISO2Countries());
    }

}