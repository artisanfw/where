<?php

namespace Artisan\Entities;

class Location
{
    public string $continent;
    public string $country;
    public string $ISO;
    public string $region;
    public string $city;
    public float $latitude;
    public float $longitude;
    public string $timezone;

    public function __construct(
        string $continent,
        string $country,
        string $ISO,
        string $region,
        string $city,
        float $latitude,
        float $longitude,
        string $timezone
    ) {
        $this->continent = $continent;
        $this->country = $country;
        $this->ISO = $ISO;
        $this->region = $region;
        $this->city = $city;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timezone = $timezone;
    }

    public function getContinent(): string
    {
        return $this->continent;
    }
    public function getCountry(): string
    {
        return $this->country;
    }
    public function getISO(): string
    {
        return $this->ISO;
    }
    public function getRegion(): string
    {
        return $this->region;
    }
    public function getCity(): string
    {
        return $this->city;
    }
    public function getLatitude(): float
    {
        return $this->latitude;
    }
    public function getLongitude(): float
    {
        return $this->longitude;
    }
    public function getTimezone(): string
    {
        return $this->timezone;
    }
}