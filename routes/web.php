<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ProjectsController;
use Illuminate\Support\Facades\Route;

Route::get('/register',[RegisterController::class,'create'])->name('register');
Route::post('/register',[RegisterController::class,'store']);
Route::get('/login',[LoginController::class,'index'])->name('login');

Route::controller(ProjectsController::class)->middleware(['auth'])->group(function () {
    Route::prefix('projects')->group(function() {
        Route::get('/{project}','show');
        Route::get('/','index');
        Route::post('/','store');
    });
});
