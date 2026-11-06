/**
 * Location picker for listing forms: a Google Map framed on the chosen
 * country, a place search restricted to that country, and a draggable pin.
 * Picking a place, clicking the map or dragging the pin writes latitude and
 * longitude back to the Livewire component, and fills the address and city
 * when the host hasn't typed their own.
 *
 * Usage (inside a Livewire component, on a wire:ignore element):
 *   x-data="locationPicker({ key, mapId, lat, lng, country, countryName })"
 *
 * Without a key, or if Google rejects it, `status` becomes 'nokey' / 'error'
 * and the form falls back to typing coordinates.
 */

let loading = null;

function loadGoogleMaps(key) {
    if (window.google?.maps?.importLibrary) {
        return Promise.resolve();
    }

    if (loading) {
        return loading;
    }

    loading = new Promise((resolve, reject) => {
        // Google's dynamic library loader, written out: it defines
        // google.maps.importLibrary and loads the API on first use.
        const maps = ((window.google ??= {}).maps ??= {});
        const params = new URLSearchParams({ key, v: 'weekly', callback: 'google.maps.__ib__' });
        const script = document.createElement('script');

        maps.__ib__ = resolve;
        script.src = `https://maps.googleapis.com/maps/api/js?${params}`;
        script.async = true;
        script.onerror = () => reject(new Error('Google Maps could not load.'));
        document.head.append(script);
    });

    return loading;
}

const round = (n) => Math.round(n * 1e6) / 1e6;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('locationPicker', (config) => ({
        status: config.key ? 'loading' : 'nokey',
        lat: config.lat ?? null,
        lng: config.lng ?? null,
        country: config.country || '',
        countryName: config.countryName || '',
        addressFromMap: false,
        manual: false,
        map: null,
        marker: null,
        geocoder: null,
        search: null,

        async init() {
            if (! config.key) return;

            // Google calls this when the key is invalid or not allowed here.
            window.gm_authFailure = () => { this.status = 'error' };

            try {
                await loadGoogleMaps(config.key);
                const [{ Map }, { AdvancedMarkerElement }, { Geocoder }] = await Promise.all([
                    google.maps.importLibrary('maps'),
                    google.maps.importLibrary('marker'),
                    google.maps.importLibrary('geocoding'),
                    google.maps.importLibrary('places'),
                ]);

                const hasPin = this.lat !== null && this.lng !== null;

                this.map = new Map(this.$refs.map, {
                    center: hasPin ? { lat: +this.lat, lng: +this.lng } : { lat: 10, lng: 20 },
                    zoom: hasPin ? 16 : 2,
                    mapId: config.mapId || 'DEMO_MAP_ID',
                    streetViewControl: false,
                    fullscreenControl: true,
                    mapTypeControl: true,
                    gestureHandling: 'cooperative',
                });

                this.geocoder = new Geocoder();
                this.marker = new AdvancedMarkerElement({
                    map: this.map,
                    position: hasPin ? { lat: +this.lat, lng: +this.lng } : null,
                    gmpDraggable: true,
                    title: 'Your property',
                });

                this.marker.addListener('dragend', () => this.pin(this.marker.position, true));
                this.map.addListener('click', (event) => this.pin(event.latLng, true));

                this.mountSearch();
                this.status = 'ready';

                if (! hasPin && this.country) {
                    this.frameCountry();
                }

                // The country select lives in Livewire; follow it.
                this.$wire.$watch('country_code', (code) => {
                    this.country = code || '';
                    this.countryName = this.$refs.country?.selectedOptions?.[0]?.text || '';
                    this.mountSearch();
                    this.frameCountry();
                });
            } catch (error) {
                console.error(error);
                this.status = 'error';
            }
        },

        /** The place search, rebuilt whenever the country changes. */
        mountSearch() {
            const { PlaceAutocompleteElement } = google.maps.places;
            const options = this.country ? { includedRegionCodes: [this.country.toLowerCase()] } : {};

            this.search?.remove();
            this.search = new PlaceAutocompleteElement(options);
            this.search.setAttribute('placeholder', 'Search for your hotel or its street…');
            this.$refs.search.replaceChildren(this.search);

            this.search.addEventListener('gmp-select', async ({ placePrediction }) => {
                const place = placePrediction.toPlace();
                await place.fetchFields({ fields: ['displayName', 'formattedAddress', 'location', 'viewport', 'addressComponents'] });

                if (place.viewport) this.map.fitBounds(place.viewport);
                this.map.setZoom(Math.max(this.map.getZoom() ?? 16, 17));
                this.pin(place.location, false);
                this.fill(place.formattedAddress, this.cityFrom(place.addressComponents, 'longText'));
            });
        },

        /** Frame the selected country. */
        async frameCountry() {
            if (! this.country || ! this.geocoder) return;

            try {
                const { results } = await this.geocoder.geocode({
                    address: this.countryName || this.country,
                    componentRestrictions: { country: this.country },
                });
                const viewport = results[0]?.geometry?.viewport;
                if (viewport) this.map.fitBounds(viewport);
            } catch (error) {
                console.warn('Could not frame the country', error);
            }
        },

        /** Typed coordinates: move the pin to them (Livewire already has them). */
        moveTo() {
            if (! this.marker || this.lat === null || this.lng === null) return;
            if (Math.abs(this.lat) > 90 || Math.abs(this.lng) > 180) return;

            const position = { lat: +this.lat, lng: +this.lng };
            this.marker.position = position;
            this.map.panTo(position);
            if ((this.map.getZoom() ?? 0) < 14) this.map.setZoom(16);
        },

        /** Move the pin and report the point to Livewire. */
        async pin(position, lookUpAddress) {
            if (! position) return;

            const lat = typeof position.lat === 'function' ? position.lat() : position.lat;
            const lng = typeof position.lng === 'function' ? position.lng() : position.lng;

            this.lat = round(lat);
            this.lng = round(lng);
            this.marker.position = { lat, lng };
            this.$wire.set('latitude', this.lat, false);
            this.$wire.set('longitude', this.lng, false);

            if (! lookUpAddress) return;

            try {
                const { results } = await this.geocoder.geocode({ location: { lat, lng } });
                if (results[0]) this.fill(results[0].formatted_address, this.cityFrom(results[0].address_components, 'long_name'));
            } catch (error) {
                console.warn('Reverse geocoding failed', error);
            }
        },

        /**
         * Suggest the address and city: only into empty fields, or fields the
         * map filled before, so a host's own wording is never overwritten.
         */
        fill(address, city) {
            if (address && (this.addressFromMap || ! this.$wire.get('address'))) {
                this.$wire.set('address', address, false);
                this.$refs.address && (this.$refs.address.value = address);
                this.addressFromMap = true;
            }

            if (city && ! this.$wire.get('city')) {
                this.$wire.set('city', city, false);
                this.$refs.city && (this.$refs.city.value = city);
            }
        },

        cityFrom(components = [], field) {
            const find = (type) => components.find((c) => (c.types || []).includes(type))?.[field];

            return find('locality') || find('postal_town') || find('administrative_area_level_2') || find('administrative_area_level_1') || '';
        },
    }));
});
