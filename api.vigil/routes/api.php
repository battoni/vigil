<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\RoleController;
use App\Modules\Auth\Controllers\UserController;
use App\Modules\Incident\Controllers\IncidentController;
use App\Modules\Monitor\Controllers\HeartbeatController;
use App\Modules\Monitor\Controllers\MonitorController;
use App\Modules\Notification\Controllers\ChannelController;
use App\Modules\Project\Controllers\ProjectController;
use App\Modules\StatusPage\Controllers\PublicStatusController;
use App\Modules\StatusPage\Controllers\StatusPageController;
use App\Modules\StatusPage\Controllers\TlsAllowedController;
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

// MONITORS
Route::get('monitors', [MonitorController::class, 'index'])->middleware('auth');
Route::get('monitors/{id}', [MonitorController::class, 'show'])->middleware('auth');
Route::post('monitors', [MonitorController::class, 'store'])->middleware('auth');
Route::patch('monitors/{id}', [MonitorController::class, 'update'])->middleware('auth');
Route::delete('monitors/{id}', [MonitorController::class, 'destroy'])->middleware('auth');

// PUBLIC STATUS PAGE (unauthenticated, rate-limited)
Route::get('status/{slug}', [PublicStatusController::class, 'show'])->middleware('throttle:60,1');

// HEARTBEAT PUSH INGRESS (unauthenticated, token-guarded, rate-limited)
Route::post('heartbeats/{token}', [HeartbeatController::class, 'ping'])->middleware('throttle:120,1');

// CADDY ON-DEMAND TLS ASK ENDPOINT (internal — only registered custom domains)
Route::get('internal/tls-allowed', [TlsAllowedController::class, 'check'])->middleware('throttle:120,1');

// STATUS PAGES (admin)
Route::get('status-pages', [StatusPageController::class, 'index'])->middleware('auth');
Route::get('status-pages/{id}', [StatusPageController::class, 'show'])->middleware('auth');
Route::post('status-pages', [StatusPageController::class, 'store'])->middleware('auth');
Route::patch('status-pages/{id}', [StatusPageController::class, 'update'])->middleware('auth');
Route::delete('status-pages/{id}', [StatusPageController::class, 'destroy'])->middleware('auth');
Route::post('status-pages/{id}/monitors/{monitorId}', [StatusPageController::class, 'attachMonitor'])->middleware('auth');
Route::delete('status-pages/{id}/monitors/{monitorId}', [StatusPageController::class, 'detachMonitor'])->middleware('auth');

// NOTIFICATION CHANNELS
Route::get('channels', [ChannelController::class, 'index'])->middleware('auth');
Route::get('channels/{id}', [ChannelController::class, 'show'])->middleware('auth');
Route::post('channels', [ChannelController::class, 'store'])->middleware('auth');
Route::patch('channels/{id}', [ChannelController::class, 'update'])->middleware('auth');
Route::delete('channels/{id}', [ChannelController::class, 'destroy'])->middleware('auth');
Route::post('channels/{id}/monitors/{monitorId}', [ChannelController::class, 'attachMonitor'])->middleware('auth');
Route::delete('channels/{id}/monitors/{monitorId}', [ChannelController::class, 'detachMonitor'])->middleware('auth');

// INCIDENTS
Route::get('incidents', [IncidentController::class, 'index'])->middleware('auth');
Route::get('incidents/{id}', [IncidentController::class, 'show'])->middleware('auth');
Route::patch('incidents/{id}/acknowledge', [IncidentController::class, 'acknowledge'])->middleware('auth');

// USERS (permission-protected)
Route::get('auth/users/check-username', [UserController::class, 'checkUsername'])->middleware('auth');
Route::get('auth/users', [UserController::class, 'index'])->middleware(['auth', 'permission:users.read']);
Route::get('auth/users/{id}', [UserController::class, 'show'])->middleware(['auth', 'permission:users.read']);
Route::post('auth/users', [UserController::class, 'store'])->middleware(['auth', 'permission:users.create']);
Route::patch('auth/users/{id}', [UserController::class, 'update'])->middleware(['auth', 'permission:users.update']);
Route::patch('auth/users/{id}/archive', [UserController::class, 'archive'])->middleware(['auth', 'permission:users.archive']);
Route::delete('auth/users/{id}', [UserController::class, 'destroy'])->middleware(['auth', 'permission:users.delete']);
