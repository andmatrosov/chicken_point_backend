<?php

return [
    'country_database_path' => env(
        'GEOIP_COUNTRY_DATABASE_PATH',
        storage_path('app/geoip/GeoLite2-Country.mmdb'),
    ),
];
