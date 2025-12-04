<template>
    <div class="relative mb-2">
        <Input
            ref="input"
            v-model="searchTerm"
            @input="fetchSuggestions"
            placeholder="Enter a location"
        />
        <button
            v-if="value"
            class="absolute top-0 right-0 h-full flex items-center justify-center px-2 cursor-pointer"
            title="Clear"
            @click="clear"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div
            class="absolute w-full top-full left-0 bg-white dark:bg-gray-850 max-h-60 z-10 overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-lg mt-1 text-gray-900 dark:text-gray-300"
            v-if="suggestedPlaces.length >= 1"
        >
            <ul>
                <li
                    v-for="(suggestion, index) in suggestedPlaces"
                    :key="index"
                >
                    <button
                        class="w-full py-2 px-3 cursor-pointer hover:bg-gray-100 focus:bg-gray-100 dark:hover:bg-gray-800 focus:dark:bg-gray-800 text-left"
                        @click="selectSuggestion(suggestion)"
                        v-text="suggestion.display"
                        :aria-label="`Select ${suggestion.display}`"
                    ></button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { Input } from '@statamic/cms/ui';
import { importLibrary } from "@googlemaps/js-api-loader";
import { ref } from 'vue';

const emit = defineEmits([
    'locationSelected',
    'cleared'
]);

const props = defineProps({
    restrictedCountries: Array,
    value: Object
});
const searchTerm = ref(null);
const suggestedPlaces = ref([]);

const addressObject = ref(props.value);

const fetchSuggestions = async () => {

    if(searchTerm.value.length < 3) {
        return;
    }

    let params = {};

    if(props.restrictedCountries.length > 1) {
        params = {
            locationRestriction: props.restrictedCountries
        };
    }

    let request = {
        input: searchTerm.value,
        ...params
    };

    const { Place, AutocompleteSessionToken, AutocompleteSuggestion } = await importLibrary('places');

    const token = new AutocompleteSessionToken();

    request.sessionToken = token;

    // Fetch suggestions
    const { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions(request);


    suggestedPlaces.value = suggestions
        .map((s) => s.placePrediction || s)   // ensure we have the prediction object
        .filter((p) => p !== null)
        .map((p) => ({
            raw: p,
            display: (p.text && typeof p.text.toString === 'function') ? p.text.toString() : (p.mainText?.text || p.text?.text || ''),
            placeId: p.placeId || p.place_id || null
        }));
}

const selectSuggestion = async (suggestion) => {
    searchTerm.value = suggestion.display;

    let place = suggestion.raw.toPlace();

    await place.fetchFields({
        fields: ['location', 'displayName', 'addressComponents', 'formattedAddress']
    });


    addressObject.value = {
        coordinates: {
            lat: place.location.lat(),
            lng: place.location.lng()
        }
    };

    const addressComponents = place.addressComponents.reduce((acc, component) => {

        const types = component.types;

        if (types.includes("street_number")) {
            acc.street_number = component.longText;
        } else if (types.includes("route")) {
            acc.route = component.longText;
        } else if (types.includes("locality")) {
            acc.locality = component.longText;
        } else if (types.includes("administrative_area_level_1")) {
            acc.administrative_area_level_1 = component.longText;
        } else if (types.includes("country")) {
            acc.country = component.longText;
        } else if (types.includes("postal_code")) {
            acc.postal_code = component.longText;
        }
        return acc;
    }, {});

    const address = {
        ...addressComponents,
        formatted_address: place.formattedAddress,
        coordinates: {
            lat: Number(place.location.lat().toFixed(6)),
            lng: Number(place.location.lng().toFixed(6)),
        },
    };

    addressObject.value = address;
    suggestedPlaces.value = [];

    // Emit update
    emit('locationSelected', addressObject.value);

    // updateOrCreateMarker({
    //     lat: address.coordinates.lat,
    //     lng: address.coordinates.lng,
    //     map: map
    // });

    // map.setCenter({
    //     lat: address.coordinates.lat,
    //     lng: address.coordinates.lng
    // });

    // map.setZoom(13);

    // update(addressObject);
};

const clear = () => {
    addressObject.value = {};
    searchTerm.value = null;

    emit('cleared');
};
</script>
