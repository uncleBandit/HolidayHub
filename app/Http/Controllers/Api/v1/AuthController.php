<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\LoginUserRequest;
use App\Http\Requests\Api\v1\RegisterUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Register a new user with role
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Assign role using Spatie
        $user->assignRole($data['role']);

        // Optional: send email verification
        if (method_exists($user, 'sendEmailVerificationNotification')) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully. Please verify your email.',
            'data' => ['user' => $user],
        ], 201);
    }

    /**
     * Login and return Sanctum token
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Optional: check email verification
        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not verified.',
            ], 403);
        }

        // Create token with role-based abilities
        $role = $user->getRoleNames()->first() ?? 'user';
        $token = $user->createToken('api-token', $this->getAbilitiesByRole($role))->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(): JsonResponse
    {
        $user = auth()->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Map roles to token abilities
     */
    protected function getAbilitiesByRole(string $role): array
    {
        return match ($role) {
            'admin' => ['*'], // all abilities
            'provider' => ['bookings:create', 'hotels:view', 'offers:manage'],
            'agent' => ['bookings:view', 'bookings:create'],
            'user' => ['bookings:create', 'hotels:view'],
            default => [],
        };
    }
}
