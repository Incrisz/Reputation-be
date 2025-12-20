<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerUIController;

Route::get('/', function () {
    return view('welcome');
});

// Swagger/API Documentation
Route::get('/api/docs', [SwaggerUIController::class, 'index'])->name('swagger.ui');

// JSON endpoint for Swagger
Route::get('/api/docs.json', function () {
    return response()->file(storage_path('api-docs/api-docs.json'), ['Content-Type' => 'application/json']);
});

// YAML endpoint for Swagger (optional)
Route::get('/api/docs.yaml', function () {
    return response()->file(storage_path('api-docs/api-docs.yaml'), ['Content-Type' => 'application/yaml']);
});

