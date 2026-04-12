<?php

/*
|--------------------------------------------------------------------------
| L5-Swagger Configuration
|--------------------------------------------------------------------------
| Package : darkaonline/l5-swagger
| Docs    : https://github.com/DarkaOnLine/L5-Swagger
|
| SECURITY NOTE:
|   Set L5_SWAGGER_GENERATE_ALWAYS=false in production.
|   Protect the /api/documentation route with auth middleware if the API is
|   not public. The generated JSON artefact lives in public/docs/ — ensure
|   your production web-server does NOT serve that directory to anonymous
|   users if your API is internal-only.
|--------------------------------------------------------------------------
*/

return [

    /*
    |----------------------------------------------------------------------
    | Named documentation groups.
    | Each key is a separate Swagger UI instance, reachable at its own URL.
    |----------------------------------------------------------------------
    */
    'default' => 'default',

    'documentations' => [

        'default' => [

            'api' => [
                'title' => 'Dorra Alaseel – Car Rental API',
            ],

            'routes' => [
                /*
                 * Swagger UI is served at:
                 *   {APP_URL}/api/documentation
                 */
                'api' => 'api/documentation',
            ],

            'paths' => [
                /*
                 * Use absolute paths so storage paths are unambiguous on the server.
                 */
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),

                /*
                 * File-name for the generated artefacts placed in public/docs/.
                 */
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',

                /*
                 * Format served to the Swagger UI: 'json' | 'yaml'
                 */
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Scan ONLY this directory for @OA annotations.
                 * Keeping it narrow (Swagger sub-folder) speeds up generation
                 * and avoids accidentally picking up third-party vendor code.
                 */
                'annotations' => [
                    base_path('app/Http/Controllers/Api/V1/Swagger'),
                ],
            ],
        ],

    ],

    /*
    |----------------------------------------------------------------------
    | Defaults shared across all documentation groups.
    |----------------------------------------------------------------------
    */
    'defaults' => [

        'routes' => [
            /*
             * Raw artefact download route (JSON / YAML).
             */
            'docs' => 'docs',

            /*
             * OAuth 2 callback (only needed when using OAuth flows).
             */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Middleware applied to each Swagger route type.
             *
             * TIP: Add your own access-control middleware here for production:
             *   'api' => ['auth:admin'],
             */
            'middleware' => [
                'api'             => [],
                'asset'           => [],
                'docs'            => [],
                'oauth2_callback' => [],
            ],

            'group_options' => [],
        ],

        'paths' => [
            /*
             * Where the generated JSON / YAML artefacts are written.
             * Must be web-accessible (inside public/).
             */
            'docs' => public_path('docs'),

            /*
             * Blade view override location (optional customisation).
             */
            'views' => base_path('resources/views/vendor/l5-swagger'),

            /*
             * Override the base URL injected into the Swagger UI.
             * Leave null to use the APP_URL-derived constant below.
             */
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            /*
             * Path to the swagger-ui static assets published by the package.
             */
            'swagger_ui_assets_path' => env(
                'L5_SWAGGER_UI_ASSETS_PATH',
                'vendor/swagger-api/swagger-ui/dist/'
            ),

            'excludes' => [],
        ],

        /*
         * Options forwarded to the underlying swagger-php analyser.
         */
        'scanOptions' => [
            'analyser'              => null,
            'analysis'              => null,
            'processors'            => [],
            'pattern'               => null,
            'exclude'               => [],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', '3.0.0'),
        ],

        /*
         * Global security definitions.
         * The actual @OA\SecurityScheme annotations live in SwaggerBaseInfo.php;
         * this section wires the config-level defaults only.
         */
        'securityDefinitions' => [
            'securitySchemes' => [],
            'security'        => [],
        ],

        /*
         * PERFORMANCE / SECURITY
         *   false  → generate once via artisan, serve cached artefact.
         *   true   → re-generate on every request (dev only).
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

        /*
         * Also write a .yaml copy alongside the .json artefact.
         */
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', true),

        /*
         * Proxy support – set to true if your app sits behind a reverse proxy
         * and you need Swagger UI to send requests through it.
         */
        'proxy' => false,

        'additional_config_url' => null,

        /*
         * Sort operations in Swagger UI: null | 'alpha' | 'method'
         */
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', 'alpha'),

        'validator_url' => null,

        'ui' => [
            'display' => [
                'dark_mode'                  => env('L5_SWAGGER_UI_DARK_MODE', false),
                /*
                 * 'none'  → all sections collapsed (recommended for large APIs)
                 * 'list'  → only the tag headers are expanded
                 * 'full'  → all operations expanded
                 */
                'doc_expansion'              => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter'                     => env('L5_SWAGGER_UI_FILTERS', true),
                'show_extensions'            => env('L5_SWAGGER_UI_SHOW_EXTENSIONS', true),
                'show_common_extensions'     => env('L5_SWAGGER_UI_SHOW_COMMON_EXTENSIONS', true),
                'try_it_out_enabled'         => env('L5_SWAGGER_TRY_IT_OUT_ENABLED', true),
                'request_snippets_enabled'   => env('L5_SWAGGER_REQUEST_SNIPPETS_ENABLED', true),
            ],

            'authorization' => [
                /*
                 * Keep the Bearer token populated across page reloads.
                 */
                'persist_authorization'        => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', true),
                'oauth2RedirectUrl'            => env(
                    'L5_SWAGGER_OAUTH_REDIRECT_URL',
                    env('APP_URL') . '/api/oauth2-callback'
                ),
                'initOAuth' => [
                    'usePkceWithAuthorizationCodeGrant' => false,
                ],
            ],
        ],

        /*
         * Runtime constants injected at annotation-generation time.
         *
         * Usage in annotations:
         *   @OA\Server(url=L5_SWAGGER_CONST_HOST)
         *
         * Override via .env:
         *   L5_SWAGGER_CONST_HOST=https://api.dorraalaseel.com
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env(
                'L5_SWAGGER_CONST_HOST',
                env('APP_URL', 'http://192.168.1.211:8001')
            ),
        ],
    ],
];
