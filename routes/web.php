<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Documentation hub
|--------------------------------------------------------------------------
|
| The landing page (/) is a documentation hub with links to:
|   - Swagger UI        → /api/documentation
|   - Docs index        → /docs  (this page)
|   - User manual       → /docs#user-manual
|   - Development guide → /docs#development
|   - Architecture      → /docs#architecture
|
| The API itself lives under /api/{tenant}/{version}/... (see routes/api.php).
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return view('docs');
});
