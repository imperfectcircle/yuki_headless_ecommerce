<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Domains\Customer\Actions\LinkGuestProfileToUser;
use App\Domains\Customer\Actions\RegisterStorefrontUser;
use App\Domains\Customer\Actions\ResendVerificationEmail;
use App\Domains\Customer\Actions\VerifyStorefrontUserEmail;
use App\Domains\Customer\Events\UserLoggedIn;
use App\Domains\Customer\Events\UserLoggedOut;
use App\Domains\Customer\Events\UserRegistered;
use App\Domains\Customer\Models\StorefrontUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest as AuthLoginRequest;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *   name="Storefront Auth",
 *   description="Authentication endpoints for storefront users"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/storefront/v1/auth/register",
     *   tags={"Storefront Auth"},
     *   summary="Register a new user",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password","password_confirmation"},
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string", format="password"),
     *       @OA\Property(property="password_confirmation", type="string", format="password")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="User registered successfully"
     *   )
     * )
     */
    public function register(
        RegisterRequest $request,
        RegisterStorefrontUser $registerUser,
        LinkGuestProfileToUser $linkGuestProfile,
    ): JsonResponse {
        $user = $registerUser->execute(
            email: $request->input('email'),
            password: $request->input('password'),
            sendVerificationEmail: true,
        );

        // Link any existing guest profile/orders
        $linkedProfile = $linkGuestProfile->execute($user, $user->email);

        // Dispatch registration event with profile linking info
        UserRegistered::dispatch(
            $user,
            hasLinkedProfile: $linkedProfile !== null,
            linkedOrderCount: $linkedProfile ? $linkedProfile->orders()->count() : null
        );

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please check your email to verify account.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified,
                ],
                'token' => $token,
                'linked_profile' => $linkedProfile ? [
                    'id' => $linkedProfile->id,
                    'has_orders' => $linkedProfile->orders()->exists(),
                ] : null,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *   path="/api/storefront/v1/auth/login",
     *   tags={"Storefront Auth"},
     *   summary="Login user",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string", format="password")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Login successful"
     *   )
     * )
     */
    public function login(AuthLoginRequest $request): JsonResponse
    {
        $user = StorefrontUser::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        //Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Dispatch ligin event
        UserLoggedIn::dispatch(
            $user,
            $request->ip(),
            $request->userAgent() ?? 'Unknown',
        );

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/storefront/v1/auth/logout",
     *   tags={"Storefront Auth"},
     *   summary="Logout user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Logout successful"
     *   )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        UserLoggedOut::dispatch($user);

        return response()->json([
            'message' => 'Logout successful.'
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/storefront/v1/auth/me",
     *   tags={"Storefront Auth"},
     *   summary="Get authenticated user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="User data"
     *   )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified
                ],
                'profile' => $profile ? [
                    'id' => $profile->id,
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'full_name' => $profile->full_name,
                    'phone' => $profile->phone,
                    'accepts_marketing' => $profile->accepts_marketing,
                ] : null,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/storefront/v1/auth/verify-email",
     *   tags={"Storefront Auth"},
     *   summary="Verify user email",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"token"},
     *       @OA\Property(property="token", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Email verified successfully"
     *   )
     * )
     */
    public function verifyEmail(
        Request $request,
        VerifyStorefrontUserEmail $verifyEmail,
    ): JsonResponse {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $user = $verifyEmail->execute($validated['token']);

            return response()->json([
                'message' => 'Email verified successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'email_verified' => true,
                    ],
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *   path="/api/storefront/v1/auth/resend-verification",
     *   tags={"Storefront Auth"},
     *   summary="Resend verification email",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Verification email sent"
     *   )
     * )
     */
    public function resendVerification(
        Request $request,
        ResendVerificationEmail $resendVerification,
    ): JsonResponse {
        try {
            $resendVerification->execute($request->user());

            return response()->json([
                'message' => 'Verification email sent.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
