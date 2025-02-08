<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'welcome'])->name('welcome');
Route::get('/about', [HomeController::class, 'about'])->name('about');

Route::get('/take-quiz', [QuizController::class, 'takeQuiz'])->middleware('auth')->name('take-quiz');

Route::get('/dashboard', [DashboardController::class, 'home'])->middleware('auth')->name('home');
Route::get('/dashboard/quizzes', [DashboardController::class, 'quizzes'])->middleware('auth')->name('quizzes');
Route::get('dashboard/create-quiz', [QuizController::class, 'create'])->middleware('auth')->name('create-quiz');
Route::get('dashboard/statistics', [DashboardController::class, 'statistics'])->middleware('auth')->name('statistics');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
