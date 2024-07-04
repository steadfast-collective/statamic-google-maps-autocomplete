# Statamic Google Maps Autocomplete

> Statamic Google Maps Autocomplete is a Statamic addon that does something pretty neat.

## Features

This addon does:

- This
- And this
- And even this

## How to Install

## How to Install

Add the following to your `composer.json`:
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/steadfast-collective/statamic-google-maps-autocomplete.git" 
    }
]
```
Then run 
```bash
composer require steadfast-collective/statamic-google-maps-autocomplete
```

run 
```
php artisan vendor:publish
```

and select `SteadfastCollective\StatamicGoogleMapsAutocomplete\ServiceProvider`

add the following to your env
```
GOOGLE_MAPS_API_KEY=XXX
GOOGLE_MAPS_ID=XXX
```

For the Map ID, refer to (Google's docs)[https://developers.google.com/maps/documentation/get-map-id]

## How to Use

Add the "Taxonomy Metadata" field to a blueprint, and specify the taxonomy it relates to.

## Usage within a template
```
{{ field_name:{property} }}
```

Available properties:
- street_number
- route
- locality
- administrative_area_1
- country
- postal_code 
