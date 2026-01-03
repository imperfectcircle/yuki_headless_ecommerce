<?php

use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;
use App\Domains\Order\Models\Order;
use App\Mail\Auth\VerifyEmailMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Mail::fake();
});

// ============================================================================
// REGISTRATION TESTS
// ============================================================================

test('user can register with valid credentials', function () {
    $response = postJson('/api/storefront/v1/auth/register', [
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => ['id', 'email', 'email_verified'],
                'token',
            ],
        ]);

    assertDatabaseHas('storefront_users', [
        'email' => 'newuser@example.com',
        'email_verified' => false,
    ]);

    expect($response->json('data.user.email_verified'))->toBeFalse();
});

test('verification email is sent on registration', function () {
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Mail::assertSent(VerifyEmailMail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});

test('registration fails with weak password', function () {
    $response = postJson('/api/storefront/v1/auth/register', [
        'email' => 'test@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

test('registration fails with duplicate email', function () {
    StorefrontUser::create([
        'email' => 'existing@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
    ]);

    $response = postJson('/api/storefront/v1/auth/register', [
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('registration fails with mismatched password confirmation', function () {
    $response = postJson('/api/storefront/v1/auth/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

// ============================================================================
// LOGIN TESTS
// ============================================================================

test('user can login with valid credentials', function () {
    $user = StorefrontUser::create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    $response = postJson('/api/storefront/v1/auth/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => ['id', 'email', 'email_verified'],
                'token',
            ],
        ]);

    expect($response->json('data.user.id'))->toBe($user->id)
        ->and($response->json('data.token'))->toBeString();
});

test('login fails with invalid credentials', function () {
    StorefrontUser::create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    $response = postJson('/api/storefront/v1/auth/login', [
        'email' => 'user@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('login fails with non-existent email', function () {
    $response = postJson('/api/storefront/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('unverified user can still login', function () {
    StorefrontUser::create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
    ]);

    $response = postJson('/api/storefront/v1/auth/login', [
        'email' => 'unverified@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertOk();
});

// ============================================================================
// EMAIL VERIFICATION TESTS
// ============================================================================

test('user can verify email with valid token', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
        'verification_token' => 'valid-token-123',
    ]);

    $response = postJson('/api/storefront/v1/auth/verify-account', [
        'token' => 'valid-token-123',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Email verified successfully.',
            'data' => [
                'user' => [
                    'email_verified' => true,
                ],
            ],
        ]);

    $user->refresh();
    expect($user->email_verified)->toBeTrue()
        ->and($user->verification_token)->toBeNull();
});

test('email verification fails with invalid token', function () {
    $response = postJson('/api/storefront/v1/auth/verify-account', [
        'token' => 'invalid-token',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Invalid or expired verification token.',
        ]);
});

test('email verification fails if already verified', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
        'verification_token' => 'token-123',
    ]);

    $response = postJson('/api/storefront/v1/auth/verify-account', [
        'token' => 'token-123',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Email already verified.',
        ]);
});

// ============================================================================
// RESEND VERIFICATION TESTS
// ============================================================================

test('can resend verification email', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
        'verification_token' => 'old-token',
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = postJson('/api/storefront/v1/auth/resend-verification', [], [
        'Authorization' => "Bearer $token",
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Verification email sent.',
        ]);

    Mail::assertSent(VerifyEmailMail::class);

    // Token should be regenerated
    $user->refresh();
    expect($user->verification_token)->not->toBe('old-token');
});

test('cannot resend verification if already verified', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = postJson('/api/storefront/v1/auth/resend-verification', [], [
        'Authorization' => "Bearer $token",
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Email already verified.',
        ]);
});

// ============================================================================
// LOGOUT TESTS
// ============================================================================

test('authenticated user can logout', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = postJson('/api/storefront/v1/auth/logout-storefront', [], [
        'Authorization' => "Bearer $token",
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Logout successful.',
        ]);

    // Token should be deleted
    expect($user->tokens()->count())->toBe(0);
});

test('logout requires authentication', function () {
    $response = postJson('/api/storefront/v1/auth/logout-storefront');

    $response->assertStatus(401);
});

// ============================================================================
// ME ENDPOINT TESTS
// ============================================================================

test('can get authenticated user data', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    $profile = CustomerProfile::create([
        'storefront_user_id' => $user->id,
        'email' => $user->email,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->getJson('/api/storefront/v1/auth/me', [
        'Authorization' => "Bearer $token",
    ]);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                    'email_verified' => true,
                ],
                'profile' => [
                    'id' => $profile->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'full_name' => 'John Doe',
                ],
            ],
        ]);
});

test('me endpoint requires authentication', function () {
    $response = $this->getJson('/api/storefront/v1/auth/me');

    $response->assertStatus(401);
});

// ============================================================================
// GUEST PROFILE LINKING TESTS
// ============================================================================

test('registration links existing guest profile and orders', function () {
    // Create guest profile
    $guestProfile = CustomerProfile::create([
        'email' => 'guest@example.com',
        'first_name' => 'Guest',
        'last_name' => 'User',
        'storefront_user_id' => null,
    ]);

    // Create guest orders
    $order1 = Order::create([
        'number' => 'ORD-2025-001',
        'status' => 'paid',
        'currency' => 'EUR',
        'subtotal' => 1000,
        'tax_total' => 220,
        'shipping_total' => 0,
        'grand_total' => 1220,
        'customer_email' => 'guest@example.com',
        'guest_checkout' => true,
    ]);

    $order2 = Order::create([
        'number' => 'ORD-2025-002',
        'status' => 'paid',
        'currency' => 'EUR',
        'subtotal' => 2000,
        'tax_total' => 440,
        'shipping_total' => 0,
        'grand_total' => 2440,
        'customer_email' => 'guest@example.com',
        'guest_checkout' => true,
    ]);

    // Register with same email
    $response = postJson('/api/storefront/v1/auth/register', [
        'email' => 'guest@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated();

    // Verify profile is linked
    $guestProfile->refresh();
    expect($guestProfile->storefront_user_id)->not->toBeNull();

    // Verify orders are linked
    $order1->refresh();
    $order2->refresh();
    expect($order1->customer_profile_id)->toBe($guestProfile->id)
        ->and($order1->guest_checkout)->toBeFalse()
        ->and($order2->customer_profile_id)->toBe($guestProfile->id)
        ->and($order2->guest_checkout)->toBeFalse();

    // Check response includes linked profile info
    expect($response->json('data.linked_profile'))->not->toBeNull()
        ->and($response->json('data.linked_profile.has_orders'))->toBeTrue();
});

test('registration without existing guest data creates clean account', function () {
    $response = postJson('/api/storefront/v1/auth/register', [
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated();

    expect($response->json('data.linked_profile'))->toBeNull();
});

// ============================================================================
// INTEGRATION TESTS
// ============================================================================

test('complete auth flow: register, verify, login, logout', function () {
    // 1. Register
    $registerResponse = postJson('/api/storefront/v1/auth/register', [
        'email' => 'complete@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $registerResponse->assertCreated();
    $user = StorefrontUser::where('email', 'complete@example.com')->first();
    expect($user->email_verified)->toBeFalse();

    // 2. Verify email
    $verifyResponse = postJson('/api/storefront/v1/auth/verify-account', [
        'token' => $user->verification_token,
    ]);

    $verifyResponse->assertOk();
    $user->refresh();
    expect($user->email_verified)->toBeTrue();

    // 3. Login
    $loginResponse = postJson('/api/storefront/v1/auth/login', [
        'email' => 'complete@example.com',
        'password' => 'Password123!',
    ]);

    $loginResponse->assertOk();
    $token = $loginResponse->json('data.token');

    // 4. Access protected endpoint
    $meResponse = $this->getJson('/api/storefront/v1/auth/me', [
        'Authorization' => "Bearer $token",
    ]);

    $meResponse->assertOk();

    // 5. Logout
    $logoutResponse = postJson('/api/storefront/v1/auth/logout-storefront', [], [
        'Authorization' => "Bearer $token",
    ]);

    $logoutResponse->assertOk();
});
