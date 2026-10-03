<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stay reel footage
    |--------------------------------------------------------------------------
    |
    | The stays section is reels-first, so every stay needs moving footage. A
    | stay that has uploaded its own video always wins; these clips are the
    | house footage used until it does. They are hotlinked from Mixkit's free
    | library (Mixkit licence, no attribution required) at 360p so cards and
    | the full-screen feed stay light on mobile data.
    |
    */

    'clips' => [
        'hotel-suite-pan' => [
            'title' => 'Luxury hotel room panning shot',
            'video' => 'https://assets.mixkit.co/videos/4196/4196-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4196/4196-thumb-360-0.jpg',
        ],
        'hotel-boutique-room' => [
            'title' => 'White luxury boutique hotel room',
            'video' => 'https://assets.mixkit.co/videos/4046/4046-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4046/4046-thumb-360-0.jpg',
        ],
        'hotel-corridor' => [
            'title' => 'Corridor of an elegant hotel',
            'video' => 'https://assets.mixkit.co/videos/34613/34613-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/34613/34613-thumb-360-0.jpg',
        ],
        'hotel-check-in' => [
            'title' => 'A family checking in a luxury hotel',
            'video' => 'https://assets.mixkit.co/videos/36736/36736-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/36736/36736-thumb-360-0.jpg',
        ],
        'hotel-resort-sea' => [
            'title' => 'Luxury resort complex with pool by the sea',
            'video' => 'https://assets.mixkit.co/videos/9902/9902-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/9902/9902-thumb-360-0.jpg',
        ],
        'hotel-waterslides' => [
            'title' => 'Sunny hotel resort with waterslides by the pool',
            'video' => 'https://assets.mixkit.co/videos/49555/49555-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/49555/49555-thumb-360-0.jpg',
        ],
        'hotel-breakfast' => [
            'title' => 'Hotel room with breakfast served',
            'video' => 'https://assets.mixkit.co/videos/4019/4019-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4019/4019-thumb-360-0.jpg',
        ],
        'hotel-breakfast-luxury' => [
            'title' => 'Luxury breakfast in a hotel',
            'video' => 'https://assets.mixkit.co/videos/15642/15642-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/15642/15642-thumb-360-0.jpg',
        ],
        'villa-garden-pool' => [
            'title' => 'Backyard of a large villa with a garden and swimming pool',
            'video' => 'https://assets.mixkit.co/videos/47286/47286-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/47286/47286-thumb-360-0.jpg',
        ],
        'villa-island' => [
            'title' => 'Tropical island landscape view',
            'video' => 'https://assets.mixkit.co/videos/4692/4692-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4692/4692-thumb-360-0.jpg',
        ],
        'villa-rooftop-pool' => [
            'title' => 'Rooftop pool of a bar near the sea',
            'video' => 'https://assets.mixkit.co/videos/3105/3105-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/3105/3105-thumb-360-0.jpg',
        ],
        'villa-luxury-pool' => [
            'title' => 'Luxury swimming pool',
            'video' => 'https://assets.mixkit.co/videos/4045/4045-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4045/4045-thumb-360-0.jpg',
        ],
        'bnb-breakfast-table' => [
            'title' => 'Breakfast at a table with bread, coffee and fruit',
            'video' => 'https://assets.mixkit.co/videos/4866/4866-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4866/4866-thumb-360-0.jpg',
        ],
        'bnb-breakfast-camp' => [
            'title' => 'Preparing breakfast at the campsite',
            'video' => 'https://assets.mixkit.co/videos/43174/43174-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/43174/43174-thumb-360-0.jpg',
        ],
        'bnb-beach-tent' => [
            'title' => 'Camping tent on a beach at sunrise',
            'video' => 'https://assets.mixkit.co/videos/25031/25031-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/25031/25031-thumb-360-0.jpg',
        ],
        'coast-turquoise' => [
            'title' => 'Aerial view of turquoise waves on a white sand beach',
            'video' => 'https://assets.mixkit.co/videos/51500/51500-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/51500/51500-thumb-360-0.jpg',
        ],
        'coast-sunset' => [
            'title' => 'View from a calm beach during a sunset',
            'video' => 'https://assets.mixkit.co/videos/44498/44498-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/44498/44498-thumb-360-0.jpg',
        ],
        'island-sandbar' => [
            'title' => 'Remote tropical island with a couple on a sandbar',
            'video' => 'https://assets.mixkit.co/videos/1575/1575-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/1575/1575-thumb-360-0.jpg',
        ],
        'wild-safari' => [
            'title' => 'Big SUV going off road on a safari in Africa',
            'video' => 'https://assets.mixkit.co/videos/45541/45541-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/45541/45541-thumb-360-0.jpg',
        ],
        'mountain-clouds' => [
            'title' => 'Clouds covering the mountains',
            'video' => 'https://assets.mixkit.co/videos/4695/4695-360.mp4',
            'poster' => 'https://assets.mixkit.co/videos/4695/4695-thumb-360-0.jpg',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Clip rotation per stay type
    |--------------------------------------------------------------------------
    |
    | Ordered so the first cards a visitor sees feel intentional. Stays are
    | assigned clips by a stable hash of their id, which keeps pagination from
    | reshuffling the footage between page loads.
    |
    */

    'reels' => [
        'hotel' => [
            'hotel-resort-sea',
            'hotel-suite-pan',
            'hotel-waterslides',
            'hotel-boutique-room',
            'hotel-check-in',
            'hotel-corridor',
            'hotel-breakfast-luxury',
            'coast-sunset',
        ],
        'villa' => [
            'villa-garden-pool',
            'villa-luxury-pool',
            'villa-island',
            'villa-rooftop-pool',
            'island-sandbar',
            'coast-turquoise',
        ],
        'bed_and_breakfast' => [
            'bnb-breakfast-table',
            'bnb-breakfast-camp',
            'bnb-beach-tent',
            'hotel-breakfast',
            'hotel-boutique-room',
            'mountain-clouds',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hero footage
    |--------------------------------------------------------------------------
    |
    | The clip that plays behind the stays hero. Any key from the list above.
    |
    */

    'hero' => 'hotel-resort-sea',

    /*
    |--------------------------------------------------------------------------
    | Presentation labels
    |--------------------------------------------------------------------------
    */

    'types' => [
        'all' => 'All stays',
        'hotel' => 'Hotels',
        'villa' => 'Villas',
        'bed_and_breakfast' => 'B&Bs',
    ],

    'type_labels' => [
        'hotel' => 'Hotel',
        'villa' => 'Villa',
        'bed_and_breakfast' => 'B&B',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reel feed tabs
    |--------------------------------------------------------------------------
    */

    'feed_tabs' => [
        'for-you' => 'For you',
        'hotels' => 'Hotels',
        'villas' => 'Villas',
        'bnb' => 'B&Bs',
    ],
];