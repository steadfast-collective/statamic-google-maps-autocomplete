import GoogleMapsField from './components/fieldtypes/GoogleMapsField.vue'
import { setOptions } from "@googlemaps/js-api-loader";

setOptions({
    key: StatamicConfig.googleMapsAutocomplete.key,
    version: "weekly"
});

Statamic.booting(() => {
    Statamic.component('google_maps_autocomplete-fieldtype', GoogleMapsField)
})
