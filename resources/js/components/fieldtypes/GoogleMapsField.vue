<template>
    <div>
        <LocationAutocomplete
            :restrictedCountries="config.included_regions"
            :includedPrimaryTypes="config.included_primary_types"
            :value="addressObject"
            @locationSelected="processSelectedSuggestion($event)"
            @cleared="clear"
        />

        <small class="text-red" v-if="!meta.hasKey">
            Google Maps API key not found
        </small>

        <input
            type="hidden"
            :value="JSON.stringify(addressObject)"
            ref="hiddenInput"
        />

        <div
            v-if="meta.hasKey"
            ref="mapEl"
            class="bg-gray-100 dark:bg-gray-800 rounded-lg"
            :style="`min-height: ${config.min_height}px;`"
        ></div>

        <div class="flex items-center mt-2 gap-4">
            <Input
                v-if="config.allow_manual_coordinates && meta.hasKey"
                ref="coordinates"
                :value="coordinateString"
                @input="manualCoordinatesEntered($event)"
                placeholder="Latitude, Longitude"
                class="max-w-3/4"
            />

            <Input
                v-if="!config.allow_manual_coordinates && meta.hasKey"
                ref="coordinates"
                :value="coordinateString"
                placeholder="Latitude, Longitude"
                class="max-w-3/4"
                readonly
                disabled
            />

            <Button
                v-if="!isObjectEmpty(addressObject)"
                :text="__('Clear')"
                @click="clear"
                class="max-w-1/4 grow"
            />
        </div>
    </div>
</template>

<script setup>
import { importLibrary } from "@googlemaps/js-api-loader";
import { Fieldtype } from '@statamic/cms';
import { Input, Button } from '@statamic/cms/ui';
import { onMounted, ref, useTemplateRef, computed } from 'vue';
import LocationAutocomplete from "../LocationAutocomplete.vue";

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update } = Fieldtype.use(emit, props);
defineExpose(expose);

var map = null;
const mapMarker = ref(null);
const mapElement = useTemplateRef('mapEl');
const displayAddress = ref(props.value ? props.value.formatted_address : null);
const addressObject = ref(props.value || {});
const parser = new DOMParser();

const isObjectEmpty = (obj) =>  {
    return Object.keys(obj).length === 0
};

const coordinateString = computed(() => {
    if(isObjectEmpty(addressObject.value)) {
        return null;
    }

    if(!addressObject.value?.coordinates || addressObject.value.coordinates === undefined) {
        return null;
    }

    return `${addressObject.value.coordinates.lat}, ${addressObject.value.coordinates.lng}`
});

onMounted(() => {
    initMap();
});

const initMap = async () => {
    if(!props.meta.hasKey) {
        return;
    }

    let defaultCoords;

    if(!isObjectEmpty(addressObject.value)) {
        defaultCoords = {
            lat: addressObject.value.coordinates.lat,
            lng: addressObject.value.coordinates.lng
        }
    } else {
        defaultCoords = {
            lat: parseCoordinate(props.config.center.lat),
            lng: parseCoordinate(props.config.center.lng)
        }
    }

    const { Map } = await importLibrary('maps');
    const { ColorScheme } = await importLibrary('core');

    map = new Map(
        mapElement.value,
        {
            zoom: isObjectEmpty(addressObject.value) ? 7 : 13,
            mapTypeId: 'roadmap',
            colorScheme: StatamicConfig.user.theme === 'dark' ? ColorScheme.DARK : ColorScheme.LIGHT,
            mapId: StatamicConfig.googleMapsAutocomplete.id,
            streetViewControl: false,
            clickableIcons: false,
            fullscreenControl: false,
            center: defaultCoords,
            draggable: props.config.allow_manual_coordinates
        }
    );

    if(!isObjectEmpty(addressObject.value)) {
        await updateOrCreateMarker({
            lat: addressObject.value.coordinates.lat,
            lng: addressObject.value.coordinates.lng,
            map: map
        })
    }

    if(props.config.allow_manual_coordinates) {
        // Add click listener to map
        map.addListener('click', async (event) => {
            const lat = Number(event.latLng.lat().toFixed(6));
            const lng = Number(event.latLng.lng().toFixed(6));

            // Update addressObject
            addressObject.value = {
                coordinates: { lat, lng }
            };

            await updateOrCreateMarker({lat, lng, map})

            update(addressObject.value);
        });
    }
};

const updateOrCreateMarker = async ({lat, lng, map} = {}) => {
    if(map === undefined) {
        throw new Error('map must be included in function call');
    }

    if(lat === undefined || lng === undefined) {
        throw new Error('Position must include lat and lng properties');
    }

    const { AdvancedMarkerElement } = await importLibrary('marker');

    // Update or create marker
    if (mapMarker.value) {
        mapMarker.value.position = { lat, lng };
    } else {
        mapMarker.value = new AdvancedMarkerElement({
            map: map,
            content: parser.parseFromString(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="size-8"><path fill="#EF4444" d="M18.364 4.636a9 9 0 0 1 0.203 12.519l-0.203 0.21 -4.243 4.242a3 3 0 0 1 -4.097 0.135l-0.144 -0.135 -4.244 -4.243A9 9 0 0 1 18.364 4.636zM12 8a3 3 0 1 0 0 6 3 3 0 0 0 0 -6z" stroke-width="1"></path></svg>',
                'image/svg+xml'
            ).documentElement,
            position: { lat, lng }
        });
    }
};

const clear = () => {
    displayAddress.value = null;
    addressObject.value = {};
    mapMarker.value.setMap(null);
    mapMarker.value = null;

    map.setCenter({
        lat: parseCoordinate(props.config.center.lat),
        lng: parseCoordinate(props.config.center.lng),
    });

    map.setZoom(6);

    update(null);
};

const processSelectedSuggestion = (location) => {
    addressObject.value = location;

    updateOrCreateMarker({
        lat: location.coordinates.lat,
        lng: location.coordinates.lng,
        map: map
    });

    map.setCenter({
        lat: location.coordinates.lat,
        lng: location.coordinates.lng
    });

    map.setZoom(13);

    update(addressObject.value);
}

const parseCoordinate = (coord) => {
    return Number(parseFloat(coord.trim()).toFixed(6));
}

const manualCoordinatesEntered = (value) => {
    value = value.data;

    if(!value) {
        return;
    }

    // Parse the coordinate string (expects format "lat, lng")
    const coords = value.split(',').map(coord => parseCoordinate(coord));

    if (coords.length === 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
        // Update the addressObject with new coordinates
        addressObject.value = {
            ...addressObject.value,
            coordinates: {
                lat: coords[0],
                lng: coords[1]
            }
        };

        // Update marker position
        updateOrCreateMarker({
            lat: coords[0],
            lng: coords[1],
            map: map
        })
        // Center map on new coordinates
        map.setCenter({
            lat: coords[0],
            lng: coords[1]
        });

        // Emit the updated address object
        update(addressObject.value)
    }
}
</script>
