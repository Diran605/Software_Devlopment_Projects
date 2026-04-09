<?php

use Illuminate\Support\Facades\Route;

// For the MVP, everything running on the web routes acts as a catch-all 
// pointing to the Vue 3 Single Page Application shell.
Route::get('{any}', function () {
    return view('app');
})->where('any', '.*');
