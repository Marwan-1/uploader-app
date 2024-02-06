<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/presigned-url', [FileController::class  , 'generatePresignedUrl']);

// Route::post('/s3/connect', [FileController::class, 'connect']);
Route::post('/s3/initiate-multipart-upload', [FileController::class, 'initiateMultipartUpload']);
Route::get('/s3/generate-presigned-url', [FileController::class, 'generatePresignedUrlForPart']);
Route::post('/s3/complete-multipart-upload', [FileController::class, 'completeMultipartUpload']);

