<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', [UploadController::class, 'index'])->name('uploads.index');
Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
Route::get('/uploads.json', [UploadController::class, 'list'])->name('uploads.list');
