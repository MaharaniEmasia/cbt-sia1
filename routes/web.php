<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;

Route::get('/', function () {
    return view('welcome');
});

// Route Ujian
Route::get('/mata-pelajaran', [ExamController::class, 'index'])->name('exam.index');
Route::get('/mata-pelajaran/{id}', [ExamController::class, 'show'])->name('exam.show');
Route::post('/mata-pelajaran/{id}/submit', [ExamController::class, 'submit'])->name('exam.submit');
Route::post('/mata-pelajaran/{id}/simpan-jawaban', [ExamController::class, 'storeAnswer'])->name('exam.store_answer');