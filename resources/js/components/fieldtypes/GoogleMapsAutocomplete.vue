<template>
    <div>
        <div class="relative">
            <text-input
                :type="inputType"
                ref="input"
                :value="displayAddress"
                @input="updateDebounced"
            />
            <button
                v-if="value"
                class="absolute top-0 right-0 h-full flex items-center justify-center px-2"
                title="Clear"
                @click="clear"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <small class="text-red-500" v-if="!meta.hasKey">
            Google Maps API key not found
        </small>
        <input
            type="hidden"
            :value="JSON.stringify(addressObject)"
            ref="hiddenInput"
        />

        <div
            ref="map"
            class="map"
        ></div>
    </div>
</template>

<script>
import { Loader } from "@googlemaps/js-api-loader";

export default {

    mixins: [Fieldtype],

    data() {
        return {
            displayAddress: this.value ? this.value.formatted_address : "",
            addressObject: this.value || {},
            autocomplete: null,
            loader: null,
            map: null,
            mapMarker: null,
            parser: null
        };
    },
    mounted() {
        this.parser = new DOMParser()

        const options = {
            componentRestrictions: { country: this.config.country_code },
            fields: ["address_components", "geometry", "formatted_address"],
        };

        this.loader = new Loader({
            apiKey: window.google_maps_api,
            libraries: ["places", "marker"]
        })

        this.loader.load().then((google) => {
            this.autocomplete = new google.maps.places.Autocomplete(
                this.$refs.input.$el.querySelector('input'),
                options
            )

            let defaultCoords

            if(!this.isObjectEmpty(this.addressObject)) {
                defaultCoords = {
                    lat: this.addressObject.coordinates.lat,
                    lng: this.addressObject.coordinates.lng,
                }
            } else {
                defaultCoords = {
                    lat: 51.60137899297304,
                    lng: -0.6384859223572998,
                }
            }

            this.map = new google.maps.Map(this.$refs.map, {
                zoom: this.isObjectEmpty(this.addressObject) ? 7 : 13,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                mapId: window.google_maps_id,
                streetViewControl: false,
                clickableIcons: false,
                fullscreenControl: false,
                center: defaultCoords
            })

            if(!this.isObjectEmpty(this.addressObject)) {
                this.mapMarker = new google.maps.marker.AdvancedMarkerElement({
                    map: this.map,
                    content: this.parser.parseFromString(
                        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                        'image/svg+xml'
                    ).documentElement,
                    position: {
                        lat: this.addressObject.coordinates.lat,
                        lng: this.addressObject.coordinates.lng
                    },
                })
            }

            this.autocomplete.addListener("place_changed", this.onPlaceChanged);
        })
    },
    methods: {
        isObjectEmpty(obj) {
            return Object.keys(obj).length === 0
        },
        updateDebounced: _.debounce(function (value) {
            this.displayAddress = value;
            this.addressObject = { ...this.addressObject, formatted_address: value };
        }, 500),

        onPlaceChanged() {
            this.mapMarker = null

            const place = this.autocomplete.getPlace();
            if (!place.geometry) {
                console.log("No details available for input: '" + place.name + "'");
                return;
            }

            const addressComponents = place.address_components.reduce(
                (acc, component) => {
                    const types = component.types;
                    if (types.includes("street_number")) {
                        acc.street_number = component.long_name;
                    } else if (types.includes("route")) {
                        acc.route = component.long_name;
                    } else if (types.includes("locality")) {
                        acc.locality = component.long_name;
                    } else if (types.includes("administrative_area_level_1")) {
                        acc.administrative_area_level_1 = component.long_name;
                    } else if (types.includes("country")) {
                        acc.country = component.long_name;
                    } else if (types.includes("postal_code")) {
                        acc.postal_code = component.long_name;
                    }
                    return acc;
                    },
                {}
            );

            const address = {
                street_number: addressComponents.street_number || "",
                route: addressComponents.route || "",
                locality: addressComponents.locality || "",
                administrative_area_level_1:
                addressComponents.administrative_area_level_1 || "",
                country: addressComponents.country || "",
                postal_code: addressComponents.postal_code || "",
                formatted_address: place.formatted_address,
                coordinates: {
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng(),
                },
            };

            this.displayAddress = address.formatted_address;
            this.addressObject = address;

            this.mapMarker = new google.maps.marker.AdvancedMarkerElement({
                map: this.map,
                content: this.parser.parseFromString(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                    'image/svg+xml'
                ).documentElement,
                position: {
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                },
            })

            this.map.setCenter({
                lat: place.geometry.location.lat(),
                lng: place.geometry.location.lng()
            })

            this.map.setZoom(13)

            this.$emit("input", address);
        },

        clear() {
            this.value = null
            this.displayAddress = null
            this.addressObject = {}
            this.mapMarker.setMap(null)
            this.mapMarker = null
            this.map.setCenter({
                lat: 51.60137899297304,
                lng: -0.6384859223572998,
            })
            this.map.setZoom(6)
            this.$emit("input", null);
        }
    },

};
</script>
