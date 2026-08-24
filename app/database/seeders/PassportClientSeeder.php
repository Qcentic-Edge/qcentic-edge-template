<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    /**
     * Ensure a personal-access client exists so the panel can mint PATs.
     */
    public function run(): void
    {
        $exists = Client::query()
            ->get()
            ->contains(fn (Client $client): bool => $client->hasGrantType('personal_access'));

        if ($exists) {
            return;
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient('Personal Access Client');
    }
}
