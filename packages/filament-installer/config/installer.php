<?php

return [
    /*
     * Master switch. After the DB lock is written, set INSTALLER_ENABLED=false
     * in the host env and redeploy. That is what opens the app for good on
     * stateless hosts (Magic Containers) — not a local lock file.
     */
    'enabled' => env('INSTALLER_ENABLED', true),

    /*
     * URI the installer lives on. Everything else redirects here until
     * INSTALLER_ENABLED=false.
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
