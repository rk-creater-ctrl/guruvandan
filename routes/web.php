<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateManagementController;
use App\Http\Controllers\EventScheduleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentManagementController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/teachers', [PublicController::class, 'gallery'])->name('teachers.index');
Route::get('/teachers/{teacher:slug}', [PublicController::class, 'teacher'])->name('teachers.show');
Route::get('/memory-wall', [PublicController::class, 'wall'])->name('wall');
Route::get('/event', [PublicController::class, 'event'])->name('event');
Route::get('/certificates/verify/{token}', [PublicController::class, 'verifyCertificate'])->name('certificates.verify');
Route::get('/teachers/{teacher:slug}/qr.svg', [QrController::class, 'teacher'])->name('teachers.qr.download');
Route::get('/teachers/{teacher:slug}/qr/print', [QrController::class, 'printTeacher'])->name('teachers.qr.print');
Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showAdminLogin'])->name('login');
    Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1')->name('admin.login.submit');
    Route::post('/login', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1');
    Route::get('/student/login', [AuthController::class, 'showStudentLogin'])->name('student.login');
    Route::post('/student/login', [AuthController::class, 'studentLogin'])->middleware('throttle:5,1')->name('student.login.submit');
    Route::get('/teacher/login', [AuthController::class, 'showTeacherLogin'])->name('teacher.login');
    Route::post('/teacher/login', [AuthController::class, 'teacherLogin'])->middleware('throttle:5,1')->name('teacher.login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function (): void {
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.save');
});
Route::get('/dashboard', [AuthController::class, 'redirectDashboard'])->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function (): void {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::post('/tributes', [StudentController::class, 'store'])->middleware('throttle:6,1')->name('tributes.store');
    Route::put('/tributes/{tribute}', [StudentController::class, 'update'])->name('tributes.update');
    Route::put('/tributes/{tribute}/resubmit', [StudentController::class, 'resubmit'])->name('tributes.resubmit');
    Route::delete('/tributes/{tribute}', [StudentController::class, 'destroy'])->name('tributes.destroy');
    Route::post('/tributes/{tribute}/like', [StudentController::class, 'like'])->middleware('throttle:20,1')->name('tributes.like');
    Route::post('/ai/generate', [StudentController::class, 'generateAi'])->name('ai.generate');
});

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile/edit', [TeacherController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [TeacherController::class, 'updateProfile'])->name('profile.update');
    Route::patch('/profile/password', [TeacherController::class, 'updatePassword'])->name('profile.password');
    Route::post('/reply', [TeacherController::class, 'saveReply'])->name('reply.save');
    Route::get('/certificate/download', [TeacherController::class, 'downloadCertificate'])->name('certificate.download');
});

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/teachers', [TeacherManagementController::class, 'index'])->name('teachers');
    Route::post('/teachers', [TeacherManagementController::class, 'store'])->name('teachers.store');
    Route::get('/teachers/{teacher}/edit', [TeacherManagementController::class, 'edit'])->name('teachers.edit');
    Route::put('/teachers/{teacher}', [TeacherManagementController::class, 'update'])->name('teachers.update');
    Route::patch('/teachers/{teacher}/toggle', [TeacherManagementController::class, 'toggle'])->name('teachers.toggle');
    Route::patch('/teachers/{teacher}/password', [TeacherManagementController::class, 'resetPassword'])->name('teachers.password');
    Route::delete('/teachers/{teacher}', [TeacherManagementController::class, 'destroy'])->name('teachers.delete');
    Route::get('/students', [StudentManagementController::class, 'index'])->name('students');
    Route::post('/students', [StudentManagementController::class, 'store'])->name('students.store');
    Route::get('/students/export', [StudentManagementController::class, 'export'])->name('students.export');
    Route::get('/students/{student}/edit', [StudentManagementController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentManagementController::class, 'update'])->name('students.update');
    Route::patch('/students/{student}/toggle', [StudentManagementController::class, 'toggle'])->name('students.toggle');
    Route::patch('/students/{student}/password', [StudentManagementController::class, 'resetPassword'])->name('students.password');
    Route::delete('/students/{student}', [StudentManagementController::class, 'destroy'])->name('students.delete');
    Route::get('/tributes', [ModerationController::class, 'index'])->name('tributes');
    Route::post('/tributes/bulk', [ModerationController::class, 'bulk'])->name('tributes.bulk');
    Route::get('/tributes/{tribute}', [ModerationController::class, 'show'])->name('tributes.show');
    Route::patch('/tributes/{tribute}', [ModerationController::class, 'update'])->name('tributes.moderate');
    Route::delete('/tributes/{tribute}', [ModerationController::class, 'destroy'])->name('tributes.destroy');
    Route::get('/event', [AdminController::class, 'event'])->name('event');
    Route::post('/event', [AdminController::class, 'saveEvent'])->name('event.save');
    Route::post('/event/{event}/schedules', [EventScheduleController::class, 'store'])->name('event.schedules.store');
    Route::put('/event/schedules/{schedule}', [EventScheduleController::class, 'update'])->name('event.schedules.update');
    Route::delete('/event/schedules/{schedule}', [EventScheduleController::class, 'destroy'])->name('event.schedules.destroy');
    Route::patch('/event/{event}/schedules/reorder', [EventScheduleController::class, 'reorder'])->name('event.schedules.reorder');
    Route::get('/certificates', [CertificateManagementController::class, 'index'])->name('certificates');
    Route::post('/certificates/generate', [AdminController::class, 'generateCertificates'])->name('certificates.generate');
    Route::post('/certificates/{teacher}/generate', [CertificateManagementController::class, 'generate'])->name('certificates.create');
    Route::post('/certificates/{teacher}/regenerate', [CertificateManagementController::class, 'regenerate'])->name('certificates.regenerate');
    Route::patch('/certificates/{certificate}/revoke', [CertificateManagementController::class, 'revoke'])->name('certificates.revoke');
    Route::get('/certificates/{teacher}/preview', [CertificateManagementController::class, 'preview'])->name('certificates.preview');
    Route::get('/certificates/{teacher}/download', [CertificateManagementController::class, 'download'])->name('certificates.download');
    Route::get('/certificates/{certificate}/qr.svg', [QrController::class, 'certificate'])->name('certificates.qr');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function (): void {
    Route::get('/admins', [AdminAccountController::class, 'index'])->name('admins');
    Route::post('/admins', [AdminAccountController::class, 'store'])->name('admins.store');
    Route::put('/admins/{account}', [AdminAccountController::class, 'update'])->name('admins.update');
    Route::patch('/admins/{account}/toggle', [AdminAccountController::class, 'toggle'])->name('admins.toggle');
    Route::patch('/admins/{account}/password', [AdminAccountController::class, 'resetPassword'])->name('admins.password');
    Route::delete('/admins/{account}', [AdminAccountController::class, 'destroy'])->name('admins.destroy');
    Route::get('/admins/{account}/activity', [AdminAccountController::class, 'activity'])->name('admins.activity');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'saveSettings'])->name('settings.save');
});
