<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/kuicip', [ProductController::class, 'kuicip'])->name('products.kuicip');
Route::get('/products/kuicip/{slug}', [ProductController::class, 'kuicipShow'])->name('products.kuicip.show');
Route::get('/products/putri-teko', [ProductController::class, 'teko'])->name('products.teko');
Route::get('/products/putri-teko/{slug}', [ProductController::class, 'tekoShow'])->name('products.teko.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::get('/export', [ExportController::class, 'show'])->name('export');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('dashboard');

    Route::get('/articles/new', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::patch('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

    Route::post('/upload', [ImageUploadController::class, 'store'])->name('upload');
});

require __DIR__.'/auth.php';
