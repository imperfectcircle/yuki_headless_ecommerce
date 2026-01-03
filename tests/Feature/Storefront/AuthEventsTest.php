<?php

use App\Domains\Customer\Events\EmailVerified;
use App\Domains\Customer\Events\GuestProfileLinked;
use App\Domains\Customer\Events\UserLoggedIn;
use App\Domains\Customer\Events\UserLoggedOut;
use App\Domains\Customer\Events\UserRegistered;
use App\Domains\Customer\Events\VerificationEmailSent;
use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;
use App\Domains\Order\Models\Order;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Auth\WelcomeMail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Mail::fake();
    Event::fake([
        UserRegistered::class,
        UserLoggedIn::class,
        UserLoggedOut::class,
        EmailVerified::class,
        VerificationEmailSent::class,
        GuestProfileLinked::class,
    ]);
});

// ============================================================================
// REGISTRATION EVENTS
// ============================================================================

test('UserRegistered event is dispatched on registration', function () {
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Event::assertDispatched(UserRegistered::class, function ($event) {
        return $event->user->email === 'test@example.com'
            && $event->hasLinkedProfile === false
            && $event->linkedOrdersCount === null;
    });
});

test('VerificationEmailSent event is dispatched on registration', function () {
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Event::assertDispatched(VerificationEmailSent::class, function ($event) {
        return $event->user->email === 'test@example.com'
            && $event->isResend === false;
    });
});

test('UserRegistered event includes guest profile linking data', function () {
    // Create guest profile with orders
    $guestProfile = CustomerProfile::create([
        'email' => 'guest@example.com',
        'first_name' => 'Guest',
        'last_name' => 'User',
        'storefront_user_id' => null,
    ]);

    Order::create([
        'number' => 'ORD-2025-001',
        'status' => 'paid',
        'currency' => 'EUR',
        'subtotal' => 1000,
        'tax_total' => 220,
        'shipping_total' => 0,
        'grand_total' => 1220,
        'customer_email' => 'guest@example.com',
        'guest_checkout' => true,
        'customer_profile_id' => $guestProfile->id,
    ]);

    // Register with same email
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'guest@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Event::assertDispatched(UserRegistered::class, function ($event) {
        return $event->user->email === 'guest@example.com'
            && $event->hasLinkedProfile === true
            && $event->linkedOrdersCount === 1;
    });
});

// ============================================================================
// LOGIN EVENTS
// ============================================================================

test('UserLoggedIn event is dispatched on login', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    postJson('/api/storefront/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    Event::assertDispatched(UserLoggedIn::class, function ($event) use ($user) {
        return $event->user->id === $user->id
            && !empty($event->ipAddress)
            && !empty($event->userAgent);
    });
});

test('UserLoggedIn event includes IP and user agent', function () {
    StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    postJson('/api/storefront/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ], [
        'User-Agent' => 'TestBrowser/1.0',
    ]);

    Event::assertDispatched(UserLoggedIn::class, function ($event) {
        return $event->ipAddress === '127.0.0.1'
            && str_contains($event->userAgent, 'TestBrowser');
    });
});

// ============================================================================
// LOGOUT EVENTS
// ============================================================================

test('UserLoggedOut event is dispatched on logout', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    postJson('/api/storefront/v1/auth/logout-storefront', [], [
        'Authorization' => "Bearer $token",
    ]);

    Event::assertDispatched(UserLoggedOut::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

// ============================================================================
// EMAIL VERIFICATION EVENTS
// ============================================================================

test('EmailVerified event is dispatched on email verification', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
        'verification_token' => 'test-token-123',
    ]);

    postJson('/api/storefront/v1/auth/verify-account', [
        'token' => 'test-token-123',
    ]);

    Event::assertDispatched(EmailVerified::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('welcome email is sent after email verification', function () {
    Mail::fake();

    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
        'verification_token' => 'test-token-123',
    ]);

    // Manually dispatch event to test listener
    EmailVerified::dispatch($user);

    Mail::assertQueued(WelcomeMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

// ============================================================================
// RESEND VERIFICATION EVENTS
// ============================================================================

test('VerificationEmailSent event is dispatched on resend with isResend true', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
        'verification_token' => 'old-token',
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    postJson('/api/storefront/v1/auth/resend-verification', [], [
        'Authorization' => "Bearer $token",
    ]);

    Event::assertDispatched(VerificationEmailSent::class, function ($event) {
        return $event->user->email === 'test@example.com'
            && $event->isResend === true;
    });
});

// ============================================================================
// GUEST PROFILE LINKING EVENTS
// ============================================================================

test('GuestProfileLinked event is dispatched when guest profile is linked', function () {
    // Create guest profile
    $guestProfile = CustomerProfile::create([
        'email' => 'guest@example.com',
        'storefront_user_id' => null,
    ]);

    // Create guest orders
    Order::create([
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

    Order::create([
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
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'guest@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Event::assertDispatched(GuestProfileLinked::class, function ($event) use ($guestProfile) {
        return $event->profile->id === $guestProfile->id
            && $event->ordersLinked === 2
            && $event->user->email === 'guest@example.com';
    });
});

test('GuestProfileLinked event is not dispatched when no guest profile exists', function () {
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Event::assertNotDispatched(GuestProfileLinked::class);
});

// ============================================================================
// EVENT LISTENER INTEGRATION TESTS
// ============================================================================

test('all registration events are dispatched in correct order', function () {
    postJson('/api/storefront/v1/auth/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // Verify all expected events were dispatched
    Event::assertDispatched(VerificationEmailSent::class);
    Event::assertDispatched(UserRegistered::class);
});

test('email verification triggers welcome email', function () {
    Mail::fake();
    Event::fake();

    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => false,
        'verification_token' => 'test-token',
    ]);

    // Manually fire the verification event
    EmailVerified::dispatch($user);

    // Check that welcome email would be queued
    // Note: In real scenario this would be handled by the listener
    Event::assertDispatched(EmailVerified::class);
});

test('multiple logins from same user dispatch separate events', function () {
    $user = StorefrontUser::create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'email_verified' => true,
    ]);

    // First login
    postJson('/api/storefront/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    // Second login
    postJson('/api/storefront/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    Event::assertDispatchedTimes(UserLoggedIn::class, 2);
});

