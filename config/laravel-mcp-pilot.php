<?php

return [

    'search' => [

        'cache_path' => base_path('bootstrap/cache/laravel-mcp-pilot.json'),

        'max_results' => 30,

        /*
         * Never indexed, no matter which root a scan starts from: dependencies,
         * generated artifacts, caches, uploads, and binary asset folders.
         */
        'excluded_paths' => [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
            'bootstrap/ssr',
            'public/assets',
            'public/build',
            'docs/images',
            'resources/js/routes',
            'resources/js/actions',
        ],

        'php' => [
            'root' => app_path(),
            'namespace' => 'App',
        ],

        'controllers_namespace' => 'App\\Http\\Controllers\\',

        'frontend_root' => resource_path('js'),

        'core_paths' => [
            base_path('bootstrap'),
            public_path(),
        ],

        'support_paths' => [
            'view' => resource_path('views'),
            'lang' => lang_path(),
            'config' => config_path(),
            'migration' => database_path('migrations'),
            'factory' => database_path('factories'),
            'seeder' => database_path('seeders'),
            'test' => base_path('tests'),
            'doc' => base_path('docs'),
        ],

        'support_extensions' => ['php', 'md', 'json'],

    ],

    'database' => [

        /*
         * Hard cap on rows returned by an unbounded SELECT (a query with no LIMIT clause
         * already gets one appended at this value) — keeps a single tool call from dumping
         * an unbounded result set into the caller's context.
         */
        'max_rows' => 200,

        /*
         * INSERT/UPDATE/DELETE are rejected by default — a tool meant to be driven by an AI
         * agent should not be able to mutate data until a consumer explicitly opts in.
         */
        'allow_write_queries' => false,

        'spatie_permission' => [
            // Auto-detected via class_exists() when spatie/laravel-permission is installed;
            // set to false to force it off even when the package is present.
            'enabled' => true,
        ],

    ],

];
