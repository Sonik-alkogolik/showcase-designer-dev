<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request for API.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $normalizedEmail = mb_strtolower(trim((string) $request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Создаём токен Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'requires_password_change' => (bool) $user->must_change_password,
        ], 200);
    }

    /**
     * Destroy the current access token (logout).
     */
    public function destroy(Request $request): Response
    {
        // Удаляем текущий токен
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}


// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Models\User;
// use Illuminate\Http\Request;
// use Illuminate\Http\Response;
// use Illuminate\Support\Facades\Hash;

// class AuthenticatedSessionController extends Controller
// {
//     /**
//      * Handle an incoming authentication request for API.
//      */
//     {
//         $request->validate([
//             'email' => ['required', 'email'],
//             'password' => ['required'],
//         ]);

//         $user = User::where('email', $request->email)->first();

//         if (! $user || ! Hash::check($request->password, $user->password)) {
//             return response()->json(['message' => 'Invalid credentials'], 401);
//         }

//         // Создаём токен Sanctum
//         $token = $user->createToken('auth-token')->plainTextToken;

//         return response()->json(['token' => $token], 200);
//     }

//     /**
//      * Destroy the current access token (logout).
//      */
//     public function destroy(Request $request): Response
//     {
//         // Удаляем текущий токен
//         $request->user()->currentAccessToken()->delete();

//         return response()->noContent();
//     }
// }

// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\Auth\LoginRequest;
// use Illuminate\Http\Request;
// use Illuminate\Http\Response;
// use Illuminate\Support\Facades\Auth;

// class AuthenticatedSessionController extends Controller
// {
//     /**
//      * Handle an incoming authentication request.
//      */
//     public function store(LoginRequest $request): Response
//     {
//         $request->authenticate();

//         $request->session()->regenerate();

//         return response()->noContent();
//     }

//     /**
//      * Destroy an authenticated session.
//      */
//     public function destroy(Request $request): Response
//     {
//         Auth::guard('web')->logout();

//         $request->session()->invalidate();

//         $request->session()->regenerateToken();

//         return response()->noContent();
//     }
// }
