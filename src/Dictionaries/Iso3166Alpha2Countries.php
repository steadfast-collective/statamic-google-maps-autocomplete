<?php

namespace SteadfastCollective\StatamicGoogleMapsAutocomplete\Dictionaries;

use Statamic\Dictionaries\Countries;

class Iso3166Alpha2Countries extends Countries
{
    protected string $valueKey = 'iso2';
    protected array $searchable = ['name', 'iso2', 'iso3'];
}
