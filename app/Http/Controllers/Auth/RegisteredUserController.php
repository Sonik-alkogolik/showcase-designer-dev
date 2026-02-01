<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // Валидация данных
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Создание пользователя
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->input('password')),
        ]);

        // Генерация 6-значного кода для Telegram
        $user->telegram_verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->save();

        // Логирование кода для тестов (НЕ возвращаем во фронтенд!)
        \Log::info('Telegram verification code generated', [
            'email' => $user->email,
            'code' => $user->telegram_verification_code,
            'user_id' => $user->id
        ]);

        // Создание токена Sanctum для авторизации
        $token = $user->createToken('auth-token')->plainTextToken;

        // Возврат данных БЕЗ кода верификации
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
            'telegram_verified' => false,
            'message' => 'Registration successful. Check your Telegram for verification code.'
        ], 201);
    }
}

// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Models\User;
// use Illuminate\Auth\Events\Registered;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Validation\Rules;
// use Illuminate\Support\Str;

// class RegisteredUserController extends Controller
// {
//     /**
//      * Handle an incoming registration request.
//      *
//      * @throws \Illuminate\Validation\ValidationException
//      */
//     // public function store(Request $request)
//     // {
//     //     // Валидация данных
//     //     $request->validate([
//     //         'name' => ['required', 'string', 'max:255'],
//     //         'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
//     //         'password' => ['required', 'confirmed', Rules\Password::defaults()],
//     //     ]);

//     //     // Создание пользователя
//     //     $user = User::create([
//     //         'name' => $request->name,
//     //         'email' => $request->email,
//     //         'password' => Hash::make($request->input('password')),
//     //     ]);

//     //     // Генерация кода для Telegram
//     //     $user->telegram_verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
//     //     $user->save();

//     //     // Событие регистрации
//     //     event(new Registered($user));

//     //     // Логиним пользователя
//     //     Auth::login($user);

//     //     // Возврат данных и telegram_verification_code во фронтенд
//     //     return response()->json([
//     //         'user' => $user,
//     //         'telegram_verification_code' => $user->telegram_verification_code,
//     //     ], 201);
//     // }
//     public function store(Request $request)
//     {
//         // Валидация данных
//         $request->validate([
//             'name' => ['required', 'string', 'max:255'],
//             'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
//             'password' => ['required', 'confirmed', Rules\Password::defaults()],
//         ]);
    
//         // Создание пользователя
//         $user = User::create([
//             'name' => $request->name,
//             'email' => $request->email,
//             'password' => Hash::make($request->input('password')),
//         ]);
    
//         // Генерация 6-значного кода для Telegram
//         $user->telegram_verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
//         $user->save();
    
//         // Создание токена Sanctum для авторизации
//         $token = $user->createToken('auth-token')->plainTextToken;
    
//         // Возврат данных и токена
//         return response()->json([
//             'user' => $user,
//             'token' => $token,
//             'telegram_verification_code' => $user->telegram_verification_code,
//             'message' => 'Registration successful. Please verify your account via Telegram.'
//         ], 201);
//     }

// }
