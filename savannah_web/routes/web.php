<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('students.index');
});

Route::get('/students', [StudentController::class, 'index'])->name('students.index');
Route::post('/students', [StudentController::class, 'store'])->name('students.store');
Route::get('/students/{id}/edit', [StudentController::class, 'edit'])->name('students.edit');
Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

Route::get('/report', [StudentController::class, 'reportCard'])->name('students.report');
Route::post('/grades', [StudentController::class, 'assignGrade'])->name('grades.store');
Route::get('/grades/{id}/edit', [StudentController::class, 'editGrade'])->name('grades.edit');
Route::put('/grades/{id}', [StudentController::class, 'updateGrade'])->name('grades.update');
Route::delete('/grades/{id}', [StudentController::class, 'destroyGrade'])->name('grades.destroy');
