<?php

use Illuminate\Support\Facades\Route;

// Website pengunjung (Beranda, Peta, Katalog, dll.) akan dibangun di tahap berikutnya.
// Untuk sementara, root diarahkan ke panel administrator.
Route::redirect('/', '/admin');
