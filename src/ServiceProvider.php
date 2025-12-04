<?php

namespace SteadfastCollective\StatamicGoogleMapsAutocomplete;

use Statamic\Statamic;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        Fieldtypes\GoogleMapsAutocomplete::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
            'resources/css/addon.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon()
    {
        Statamic::provideToScript([
            'googleMapsAutocomplete' => [
                'key' => config('statamic-google-maps-autocomplete.google_maps_api_key'),
                'id' => config('statamic-google-maps-autocomplete.google_maps_id')
            ]
        ]);
    }
}
