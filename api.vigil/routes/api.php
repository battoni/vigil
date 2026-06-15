<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\RoleController;
use App\Modules\Auth\Controllers\UserController;
use App\Modules\Project\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// AUTH
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('auth/me', [AuthController::class, 'me'])->middleware('auth');
Route::get('auth/permission-groups', [AuthController::class, 'permissionGroups'])->middleware('auth');
Route::get('auth/support-names', [AuthController::class, 'supportNames']);

// ROLES
Route::get('auth/roles', [RoleController::class, 'index']);
Route::get('auth/roles/{id}', [RoleController::class, 'show']);
Route::post('auth/roles', [RoleController::class, 'store'])->middleware('auth');
Route::patch('auth/roles/{id}', [RoleController::class, 'update'])->middleware('auth');
Route::patch('auth/roles/{id}/permissions', [RoleController::class, 'updatePermissions'])->middleware('auth');
Route::delete('auth/roles/{id}', [RoleController::class, 'destroy'])->middleware('auth');

// PROJECTS
Route::get('projects', [ProjectController::class, 'index'])->middleware('auth');
Route::get('projects/{id}', [ProjectController::class, 'show'])->middleware('auth');
Route::post('projects', [ProjectController::class, 'store'])->middleware('auth');
Route::patch('projects/{id}', [ProjectController::class, 'update'])->middleware('auth');
Route::delete('projects/{id}', [ProjectController::class, 'destroy'])->middleware('auth');

// USERS (permission-protected)
Route::get('auth/users/check-username', [UserController::class, 'checkUsername'])->middleware('auth');
Route::get('auth/users', [UserController::class, 'index'])->middleware(['auth', 'permission:users.read']);
Route::get('auth/users/{id}', [UserController::class, 'show'])->middleware(['auth', 'permission:users.read']);
Route::post('auth/users', [UserController::class, 'store'])->middleware(['auth', 'permission:users.create']);
Route::patch('auth/users/{id}', [UserController::class, 'update'])->middleware(['auth', 'permission:users.update']);
Route::patch('auth/users/{id}/archive', [UserController::class, 'archive'])->middleware(['auth', 'permission:users.archive']);
Route::delete('auth/users/{id}', [UserController::class, 'destroy'])->middleware(['auth', 'permission:users.delete']);
