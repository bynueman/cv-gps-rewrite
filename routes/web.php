<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
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
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/export', [ExportController::class, 'show'])->name('export');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::middleware(['auth', 'noindex'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/new', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::patch('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

    Route::post('/upload', [ImageUploadController::class, 'store'])->name('upload');

    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/new', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
    Route::get('/partners/new', [PartnerController::class, 'create'])->name('partners.create');
    Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
    Route::get('/partners/{partner}/edit', [PartnerController::class, 'edit'])->name('partners.edit');
    Route::patch('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
    Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    Route::middleware('superadmin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/new', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.index');
        Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
