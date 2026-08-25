<?php

return [
    /*
     * Master switch. Set INSTALLER_ENABLED=false once you never want the
     * installer again (e.g. after the first production deploy).
     */
    'enabled' => env('INSTALLER_ENABLED', true),

    /*
     * URI the installer lives on. Everything else redirects here until the
     * lock file exists.
     */
    'path' => env('INSTALLER_PATH', 'install'),

    /*
     * Environment variables that must be present and non-empty before the
     * "Run migrations" button unlocks.
     */
    'required_env' => [
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_URL',
    ],

    /*
     * Written after a successful migration run. While it exists the
     * installer route 404s and the middleware passes through.
     */
    'lock_file' => storage_path('app/.installer-installed'),

    /*
     * Offer name/email/password fields on the installer and create the first
     * user right after migrations succeed.
     */
    'create_user' => env('INSTALLER_CREATE_USER', true),

    /*
     * Eloquent model the first user is created with.
     */
    'user_model' => env('INSTALLER_USER_MODEL', 'App\\Models\\User'),

    /*
     * Seeder classes run after migrate and before the first user is created.
     * Apps that use Spatie roles / Passport should list RoleSeeder and
     * PassportClientSeeder here so assignRole('super_admin') does not fail.
     *
     * @var list<class-string>
     */
    'seeders' => [],
];
