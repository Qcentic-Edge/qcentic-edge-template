<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(DatabaseMigrations::class);

afterEach(function () {
    Passport::$personalAccessTokensExpireIn = null;
    Passport::$tokensExpireIn = null;
});

test('get api user without a token is unauthorized', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('get api user with a bad token is unauthorized', function () {
    $this->getJson('/api/user', [
        'Authorization' => 'Bearer not-a-real-jwt',
    ])->assertUnauthorized();
});

test('get api user with an expired token is unauthorized', function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');

    Passport::personalAccessTokensExpireIn(now()->subHour());

    $user = User::factory()->create();
    $token = $user->createToken('expired')->accessToken;

    $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();
});

test('personal access token returns that user as json', function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');

    $other = User::factory()->create([
        'name' => 'Other User',
        'email' => 'other@example.com',
    ]);

    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $token = $user->createToken('pat')->accessToken;

    $response = $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response
        ->assertOk()
        ->assertExactJson([
            'id' => $user->id,
            'email' => 'ada@example.com',
            'name' => 'Ada Lovelace',
        ]);

    expect($response->json('id'))->not->toBe($other->id);
});

test('authorization code and refresh token issue a user scoped api token', function () {
    Passport::authorizationView(fn (): Response => response('consent'));

    $redirectUri = 'http://127.0.0.1/callback';
    $state = 'oauth-state';
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Auth Code Client',
        [$redirectUri],
    );

    $this->actingAs($user)
        ->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]))
        ->assertOk();

    $approved = $this->actingAs($user)->post('/oauth/authorize', [
        'auth_token' => session('authToken'),
    ]);

    $approved->assertRedirect();

    $query = [];
    parse_str((string) parse_url((string) $approved->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query)->toHaveKey('code');
    expect($query['state'] ?? null)->toBe($state);

    asGuest();

    $tokens = $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'client_secret' => $client->plainSecret,
        'redirect_uri' => $redirectUri,
        'code' => $query['code'],
    ]);

    $tokens
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token', 'token_type', 'expires_in']);

    $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$tokens->json('access_token'),
    ])
        ->assertOk()
        ->assertExactJson([
            'id' => $user->id,
            'email' => 'ada@example.com',
            'name' => 'Ada Lovelace',
        ]);

    $refreshed = $this->postJson('/oauth/token', [
        'grant_type' => 'refresh_token',
        'refresh_token' => $tokens->json('refresh_token'),
        'client_id' => $client->getKey(),
        'client_secret' => $client->plainSecret,
    ]);

    $refreshed
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token', 'token_type', 'expires_in']);

    expect($refreshed->json('access_token'))->not->toBe($tokens->json('access_token'));

    $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$refreshed->json('access_token'),
    ])
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', 'ada@example.com');
});

test('client credentials token cannot access api user', function () {
    $client = app(ClientRepository::class)->createClientCredentialsGrantClient('Machine Client');

    $tokens = $this->postJson('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->getKey(),
        'client_secret' => $client->plainSecret,
    ]);

    $tokens
        ->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

    expect($tokens->json())->not->toHaveKey('refresh_token');

    $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$tokens->json('access_token'),
    ])->assertUnauthorized();
});

test('password grant is rejected and is not registered', function () {
    expect(Passport::$passwordGrantEnabled)->toBeFalse();

    $this->postJson('/oauth/token', [
        'grant_type' => 'password',
        'client_id' => 'test-client',
        'client_secret' => 'secret',
        'username' => 'ada@example.com',
        'password' => 'password',
        'scope' => '',
    ])
        ->assertStatus(400)
        ->assertJsonPath('error', 'unsupported_grant_type');
});
