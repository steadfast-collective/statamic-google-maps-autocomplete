import GoogleMapsAutocomplete from './components/fieldtypes/GoogleMapsAutocomplete.vue'

Statamic.booting(() => {
    Statamic.component('google_maps_autocomplete-fieldtype', GoogleMapsAutocomplete)
})
