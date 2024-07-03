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
        Statamic::inlineScript(__("window.google_maps_api = ':key'", [
            'key' => config('statamic-google-maps-autocomplete.google_maps_api_key')
        ]));

        Statamic::inlineScript(__("window.google_maps_id = ':id'", [
            'id' => config('statamic-google-maps-autocomplete.google_maps_id')
        ]));
    }
}
