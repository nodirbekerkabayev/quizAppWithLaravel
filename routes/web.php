<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'welcome'])->name('welcome');
Route::get('/about', [HomeController::class, 'about'])->name('about');

Route::get('/take-quiz/{slug}', [QuizController::class, 'takeQuiz'])->middleware('auth')->name('take-quiz');

Route::prefix('dashboard')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'home'])->middleware('auth')->name('dashboard');
    Route::get('/quizzes', [QuizController::class, 'index'])->middleware('auth')->name('quizzes');

    Route::get('/quizzes/{quiz}', [QuizController::class, 'edit'])->middleware('auth')->name('edit-quiz');
    Route::post('/quizzes/{quiz}/update', [QuizController::class, 'update'])->name('update-quiz');

    Route::get('/statistics', [DashboardController::class, 'statistics'])->middleware('auth')->name('statistics');

    Route::get('/create-quiz', [QuizController::class, 'create'])->middleware('auth')->name('create-quiz');
    Route::post('/create-quiz', [QuizController::class, 'store'])->middleware('auth')->name('store-quiz');

    Route::get('/delete/quiz/{quiz}', [QuizController::class, 'destroy'])->middleware('auth')->name('delete-quiz');

});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
