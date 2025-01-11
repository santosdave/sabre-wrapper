<?php

namespace Santosdave\SabreWrapper\Models\Utility;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class GeoSearchRequest implements SabreRequest
{
    public const CATEGORY_HOTEL = 'HOTEL';
    public const CATEGORY_CAR = 'CAR';
    public const CATEGORY_AIRPORT = 'AIRPORT';

    public const UNIT_MILE = 'MI';
    public const UNIT_KILOMETER = 'KM';

    private string $searchValue;
    private string $category;
    private ?int $radius = null;
    private string $unit = self::UNIT_MILE;
    private int $maxResults = 100;
    private string $valueContext = 'CODE'; // CODE or NAME
    private ?string $countryCode = null;

    public function __construct(string $searchValue, string $category)
    {
        $this->searchValue = $searchValue;
        $this->category = $category;
    }

    public function setRadius(?int $radius): self
    {
        $this->radius = $radius;
        return $this;
    }

    public function setUnit(string $unit): self
    {
        if (!in_array($unit, [self::UNIT_MILE, self::UNIT_KILOMETER])) {
            throw new SabreApiException('Invalid unit. Use MI or KM.');
        }
        $this->unit = $unit;
        return $this;
    }

    public function setMaxResults(int $maxResults): self
    {
        $this->maxResults = $maxResults;
        return $this;
    }

    public function setValueContext(string $context): self
    {
        $this->valueContext = $context;
        return $this;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->searchValue)) {
            throw new SabreApiException('Search value is required');
        }

        if (!in_array($this->category, [self::CATEGORY_HOTEL, self::CATEGORY_CAR, self::CATEGORY_AIRPORT])) {
            throw new SabreApiException('Invalid category');
        }

        if ($this->radius !== null && $this->radius <= 0) {
            throw new SabreApiException('Radius must be greater than 0');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'GeoSearchRQ' => [
                'version' => '1',
                'GeoRef' => [
                    'Category' => $this->category,
                    'MaxResults' => $this->maxResults,
                    'RefPoint' => [
                        'Value' => $this->searchValue,
                        'ValueContext' => $this->valueContext
                    ]
                ]
            ]
        ];

        if ($this->radius !== null) {
            $request['GeoSearchRQ']['GeoRef']['Radius'] = $this->radius;
            $request['GeoSearchRQ']['GeoRef']['UOM'] = $this->unit;
        }

        if ($this->countryCode) {
            $request['GeoSearchRQ']['GeoRef']['RefPoint']['CountryCode'] = $this->countryCode;
        }

        return $request;
    }
}
