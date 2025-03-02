<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserDietaryProfileController;
use App\Http\Controllers\MedicalConditionController;
use App\Http\Controllers\DietaryRestrictionController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dietary Profile Routes
    Route::prefix('dietary-profile')->name('dietary-profile.')->group(function () {
        Route::get('/', [UserDietaryProfileController::class, 'index'])->name('index');
        Route::get('/create', [UserDietaryProfileController::class, 'create'])->name('create');
        Route::post('/', [UserDietaryProfileController::class, 'store'])->name('store');
        Route::get('/{userDietaryProfile}', [UserDietaryProfileController::class, 'show'])->name('show');
        Route::get('/{userDietaryProfile}/edit', [UserDietaryProfileController::class, 'edit'])->name('edit');
        Route::put('/{userDietaryProfile}', [UserDietaryProfileController::class, 'update'])->name('update');
        Route::delete('/{userDietaryProfile}', [UserDietaryProfileController::class, 'destroy'])->name('destroy');
        Route::put('/{userDietaryProfile}/set-active', [UserDietaryProfileController::class, 'setActive'])->name('set-active');
    });

    // API Routes for Medical Conditions and Dietary Restrictions
    Route::get('/medical-conditions', [MedicalConditionController::class, 'index'])->name('medical-conditions.index');
    Route::get('/dietary-restrictions', [DietaryRestrictionController::class, 'index'])->name('dietary-restrictions.index');
    Route::get('/medical-conditions/{medicalCondition}/restrictions', [MedicalConditionController::class, 'restrictions'])
        ->name('medical-conditions.restrictions');
});
