<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-docs', function () {
    return file_get_contents(public_path('docs/swagger-ui.html'));
});