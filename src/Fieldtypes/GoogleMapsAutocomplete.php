<?php

namespace SteadfastCollective\StatamicGoogleMapsAutocomplete\Fieldtypes;

use Statamic\Fields\Fieldtype;

class GoogleMapsAutocomplete extends Fieldtype
{
    protected $icon = 'map-search';

    /**
     * The blank/default value.
     *
     * @return array
     */
    public function defaultValue()
    {
        return null;
    }

    /**
     * Pre-process the data before it gets sent to the publish page.
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function preProcess($data)
    {
        return $data;
    }

    /**
     * Process the data before it gets saved.
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function process($data)
    {
        return $data;
    }

    /**
     * Preload the fieldtype with any required data.
     *
     * @return array
     */
    public function preload()
    {
        return [
            'hasKey' => config('statamic-google-maps-autocomplete.google_maps_api_key') !== null,
        ];
    }

    /**
     * Configuration fields.
     *
     * @return array
     */
    protected function configFieldItems(): array
    {
        return [
            'included_regions' => [
                'display' => 'Included Regions',
                'instructions' => 'Restrict autocomplete suggestions to results in the selected regions. Select up to 15 countries.',
                'type' => 'dictionary',
                'multiple' => true,
                'dictionary' => 'iso3166_alpha2_countries',
                'max_items' => 15,
                'searchable' => true,
                'width' => 100
            ],
            'included_primary_types' => [
                'display' => 'Included Primary Types',
                'instructions' => 'Restrict autocomplete suggestions to places with the selected primary types. Select up to 5 values.',
                'type' => 'dictionary',
                'multiple' => true,
                'dictionary' => 'google_place_types',
                'max_items' => 5,
                'searchable' => true,
                'width' => 100
            ],
            'allow_manual_coordinates' => [
                'display' => 'Allow Manual Coordinates Entry',
                'instructions' => 'Allow the user to enter coordinates manually via text input or by clicking the map.',
                'type' => 'toggle',
                'default' => false,
                'width' => 50
            ],
            'min_height' => [
                'display' => 'Map Height',
                'instructions' => 'Set the minimum height of the map. (in pixels)',
                'type' => 'integer',
                'default' => 350,
                'width' => 50
            ],
            'center' => [
                'display' => "Map Center",
                'instructions' => 'Default center for the map.',
                'type' => 'group',
                'width' => 100,
                'fields' => [
                    [
                        'handle' => 'lat',
                        'field' => [
                            'display' => "Latitude",
                            'type' => 'text',
                            'width' => 50,
                            'default' => '51.60137899297304'
                        ]
                    ],
                    [
                        'handle' => 'lng',
                        'field' => [
                            'display' => "Longitude",
                            'type' => 'text',
                            'width' => 50,
                            'default' => '-0.6384859223572998'
                        ]
                    ],
                ]
            ]
        ];
    }
}
