<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('login.page');
Route::view('/surat', 'surat.index')->name('surat.index');
Route::view('/surat/tambah', 'surat.create')->name('surat.create');

// ➜ Halaman Edit Surat (admin-only di-guard via JS ensureAdmin())
Route::view('/surat/{id}/edit', 'surat.edit')->name('surat.edit');

/** Admin-only (UI) */
Route::view('/admin/users', 'users.index')->name('users.index');
Route::view('/admin/users/create', 'users.create')->name('users.create');
Route::view('/admin/users/{id}/edit', 'users.edit')->name('users.edit');