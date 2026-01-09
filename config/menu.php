<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Circular Menu Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the circular menu on the home page.
    | Each menu item can have sublevels (children) for nested navigation.
    |
    */

    'items' => [
        // Example menu item structure:
        [
            'id' => 'unique_id',
            'name' => 'Menu Item 1',
            'translation_key' => 'moonshine.types.values.resource_name', // Optional: translation key
            'href' => '#', // Route name or URL
            'href_params' => ['param' => 'value'], // Optional: route parameters
            'auth_required' => true, // Optional: requires authentication (default: false)
            'guest_href' => 'resources.stub', // Optional: route for non-authenticated users
            'guest_href_params' => ['type_id' => 1], // Optional: parameters for guest route
            'children' => [ // Optional: sublevels
                [
                    'id' => 'sub_item_1',
                    'name' => 'Sub Item 1',
                    'href' => 'route.name1',
                ],
            ],
        ],
        [
            'id' => 'unique_id',
            'name' => 'Menu Item 2',
            'translation_key' => 'moonshine.types.values.resource_name', // Optional: translation key
            'href' => '#', // Route name or URL
            'href_params' => ['param' => 'value'], // Optional: route parameters
            'auth_required' => true, // Optional: requires authentication (default: false)
            'guest_href' => 'resources.stub', // Optional: route for non-authenticated users
            'guest_href_params' => ['type_id' => 1], // Optional: parameters for guest route
            'children' => [ // Optional: sublevels
                [
                    'id' => 'sub_item_2',
                    'name' => 'Sub Item 2',
                    'href' => 'route.name2',
                ],
            ],
        ],
        [
            'id' => 'unique_id',
            'name' => 'Menu Item 3',
            'translation_key' => 'moonshine.types.values.resource_name', // Optional: translation key
            'href' => '#', // Route name or URL
            'href_params' => ['param' => 'value'], // Optional: route parameters
            'auth_required' => true, // Optional: requires authentication (default: false)
            'guest_href' => 'resources.stub', // Optional: route for non-authenticated users
            'guest_href_params' => ['type_id' => 1], // Optional: parameters for guest route
            'children' => [ // Optional: sublevels
                [
                    'id' => 'sub_item_3',
                    'name' => 'Sub Item 3',
                    'href' => 'route.name3',
                ],
            ],
        ],

        // Resource types from database
        // This will be populated dynamically from Type model
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Type: 'database' or 'config'
    |--------------------------------------------------------------------------
    |
    | 'database' - Load menu items from database (Type model)
    | 'config' - Use items defined in 'items' array above
    |
    */
    'source' => 'config',

    /*
    |--------------------------------------------------------------------------
    | Database Configuration (when source is 'database')
    |--------------------------------------------------------------------------
    */
    'database' => [
        'model' => \App\Models\Type::class,
        'where' => [
            'resource_type' => 'resources',
        ],
        'order_by' => 'name',
        'order_direction' => 'asc',
        'name_field' => 'name',
        'translation_prefix' => 'moonshine.types.values.',
        'route_name' => 'resources.by_type',
        'route_param' => 'id', // Field name to use as route parameter
        'guest_route' => 'resources.stub',
    ],
];




