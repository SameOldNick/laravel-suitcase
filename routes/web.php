<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/**
 * This route is used to create a symbolic link to the storage directory.
 * This is needed because cPanel does not allow symbolic links to be created from the file manager.
 */
Route::get(config('hostpack.storage_route'), function () {
    Artisan::call('storage:link');

    return 'Please try again.';
});
