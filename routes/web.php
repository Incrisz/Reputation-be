<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Swagger/API Documentation
Route::get('/api/docs', function () {
    return redirect('/api/docs/');
});

Route::get('/api/docs/', function () {
    return view('vendor.l5-swagger.index');
});
