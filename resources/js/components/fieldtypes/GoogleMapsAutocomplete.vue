<template>
    <div>
        <div class="gma-relative gma-mb-2">
            <text-input
                :type="inputType"
                ref="input"
                :value="displayAddress"
                @input="updateDebounced"
            />
            <button
                v-if="value"
                class="gma-absolute gma-top-0 gma-right-0 gma-h-full gma-flex gma-items-center gma-justify-center gma-px-2"
                title="Clear"
                @click="clear"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <small class="gma-text-red-500" v-if="!meta.hasKey">
            Google Maps API key not found
        </small>
        <input
            type="hidden"
            :value="JSON.stringify(addressObject)"
            ref="hiddenInput"
        />

        <div
            ref="map"
            class="gma-bg-gray-400 rounded"
            :style="`min-height: ${config.min_height}px;`"
        ></div>

        <text-input
            v-if="addressObject && config.allow_manual_coordinates"
            :type="inputType"
            ref="coordinates"
            :value="coordinateString"
            @input="manualCoordinatesEntered"
            class="gma-mt-2"
        />

        <span
            v-if="!config.allow_manual_coordinates"
            class="gma-text-xs gma-text-gray-800"
            v-text="coordinateString"
        ></span>
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
                center: defaultCoords,
                draggable : this.config.allow_manual_coordinates
            })

            if(!this.isObjectEmpty(this.addressObject)) {
                this.mapMarker = new google.maps.marker.AdvancedMarkerElement({
                    map: this.map,
                    content: this.parser.parseFromString(
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="gma-size-8 gma-text-red-500"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" /></svg>',
                        'image/svg+xml'
                    ).documentElement,
                    position: {
                        lat: this.addressObject.coordinates.lat,
                        lng: this.addressObject.coordinates.lng
                    },
                })
            }

            this.autocomplete.addListener("place_changed", this.onPlaceChanged);

            if(this.config.allow_manual_coordinates) {
                // Add click listener to map
                this.map.addListener('click', (event) => {
                    const lat = Number(event.latLng.lat().toFixed(6));
                    const lng = Number(event.latLng.lng().toFixed(6));

                    // Update addressObject
                    this.addressObject = {
                        ...this.addressObject,
                        coordinates: { lat, lng }
                    };

                    // Update or create marker
                    if (this.mapMarker) {
                        this.mapMarker.position = { lat, lng };
                    } else {
                        this.mapMarker = new google.maps.marker.AdvancedMarkerElement({
                            map: this.map,
                            content: this.parser.parseFromString(
                                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                                'image/svg+xml'
                            ).documentElement,
                            position: { lat, lng }
                        });
                    }

                    // Emit the updated value
                    this.$emit("input", this.addressObject);
                });
            }
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
                    lat: Number(place.geometry.location.lat().toFixed(6)),
                    lng: Number(place.geometry.location.lng().toFixed(6)),
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

        manualCoordinatesEntered: function(value) {
            // Parse the coordinate string (expects format "lat, lng")
            const coords = value.split(',').map(coord => Number(parseFloat(coord.trim()).toFixed(6)));

            if (coords.length === 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                // Update the addressObject with new coordinates
                this.addressObject = {
                    ...this.addressObject,
                    coordinates: {
                        lat: coords[0],
                        lng: coords[1]
                    }
                };

                // Update marker position
                if (this.mapMarker) {
                    this.mapMarker.position = {
                        lat: coords[0],
                        lng: coords[1]
                    };
                }

                // Center map on new coordinates
                this.map.setCenter({
                    lat: coords[0],
                    lng: coords[1]
                });

                // Emit the updated address object
                this.$emit("input", this.addressObject);
            }
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
    computed: {
        coordinateString: function() {
            if(this.addressObject.coordinates === undefined) {
                return ''
            }

            return `${this.addressObject.coordinates.lat}, ${this.addressObject.coordinates.lng}`
        }
    },
};
</script>
