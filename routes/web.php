<?php

use Illuminate\Support\Facades\Route;

Route::get('/purge-user-data', function (\Illuminate\Http\Request $request) {
    $email = $request->query('email');
    if (!$email) return 'Provide email query param';
    
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) return "User $email not found";
    
    $shops = $user->shops;
    $shopCount = $shops->count();
    $productCount = 0;
    
    foreach ($shops as $shop) {
        $productCount += $shop->products()->count();
        $shop->products()->delete();
        $shop->delete();
    }
    
    return "Purged $shopCount shops and $productCount products for user {$user->email}";
});

Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

require __DIR__.'/auth.php';

Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
