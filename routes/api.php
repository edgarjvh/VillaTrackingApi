<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/getUsers', [\App\Http\Controllers\UsersController::class, 'getUsers']);
Route::post('/getUser', [\App\Http\Controllers\UsersController::class, 'getUser']);
Route::post('/registerUser', [\App\Http\Controllers\UsersController::class, 'registerUser']);
Route::post('/validateUser', [\App\Http\Controllers\UsersController::class, 'validateUser']);
Route::post('/validateLogin', [\App\Http\Controllers\UsersController::class, 'validateLogin']);

Route::post('/getDevices', [\App\Http\Controllers\DevicesController::class, 'getDevices']);
Route::post('/getDeviceById', [\App\Http\Controllers\DevicesController::class, 'getDeviceById']);
Route::post('/getDevicesByUser', [\App\Http\Controllers\DevicesController::class, 'getDevicesByUser']);
Route::post('/saveDevice', [\App\Http\Controllers\DevicesController::class, 'saveDevice']);
Route::post('/deleteDevice', [\App\Http\Controllers\DevicesController::class, 'deleteDevice']);
Route::post('/getDeviceHistory', [\App\Http\Controllers\DevicesController::class, 'getDeviceHistory']);

Route::post('/getGroups', [\App\Http\Controllers\GroupsController::class, 'getGroups']);
Route::post('/getGroupsById', [\App\Http\Controllers\GroupsController::class, 'getGroupsById']);
Route::post('/getGroupsByUser', [\App\Http\Controllers\GroupsController::class, 'getGroupsByUser']);
Route::post('/getGroupDevices', [\App\Http\Controllers\GroupsController::class, 'getGroupDevices']);
Route::post('/saveGroup', [\App\Http\Controllers\GroupsController::class, 'saveGroup']);
Route::post('/deleteGroup', [\App\Http\Controllers\GroupsController::class, 'deleteGroup']);
Route::post('/saveGroupDevices', [\App\Http\Controllers\GroupsController::class, 'saveGroupDevices']);

Route::post('/getGeofences', [\App\Http\Controllers\GeofencesController::class, 'getGeofences']);
Route::post('/getGeofenceById', [\App\Http\Controllers\GeofencesController::class, 'getGeofenceById']);
Route::post('/getGeofencesByUser', [\App\Http\Controllers\GeofencesController::class, 'getGeofencesByUser']);
Route::post('/saveGeofence', [\App\Http\Controllers\GeofencesController::class, 'saveGeofence']);
Route::post('/deleteGeofence', [\App\Http\Controllers\GeofencesController::class, 'deleteGeofence']);
Route::post('/getGeofenceDevices', [\App\Http\Controllers\GeofencesController::class, 'getGeofenceDevices']);
Route::post('/saveGeofenceDevices', [\App\Http\Controllers\GeofencesController::class, 'saveGeofenceDevices']);


