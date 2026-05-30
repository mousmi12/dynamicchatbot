<?php


use App\Http\Controllers\AdminController;
use App\Http\Controllers\ButtonController;
use App\Http\Controllers\ChatbotConfigController;
use App\Http\Controllers\ChatbotWizardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;


// Public Chat Routes (No auth needed)
Route::get('/{company_slug}/chat', [ChatController::class, 'showChat'])->middleware('company.config');
Route::post('/{company_slug}/chat', [ChatController::class, 'handle'])->middleware('company.config');

// Admin Routes with Authentication
Route::prefix('admin')->name('admin.')->group(function () {

    // Login Routes (No middleware)
    Route::get('/login', [AdminController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminController::class, 'handleLogin']);

    // Protected Admin Routes
    Route::middleware('auth')->group(function () {

        // Dashboard & System
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
        Route::get('/system-status', [AdminController::class, 'systemStatus'])->name('system.status');
        Route::post('/cache/clear', [AdminController::class, 'clearCache'])->name('cache.clear');
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

        // Chatbot Wizard - Unified Management
        Route::prefix('companies/{company}/chatbotwizard')->name('chatbotwizard.')->group(function () {
            Route::get('/', [ChatbotWizardController::class, 'index'])->name('index');

            // Config Management
            Route::post('/config', [ChatbotWizardController::class, 'saveConfig'])->name('save-config');
            Route::delete('/config/{config}', [ChatbotWizardController::class, 'deleteConfig'])->name('delete-config');

            // Button Template Management
            Route::post('/button', [ChatbotWizardController::class, 'saveButton'])->name('save-button');
            Route::delete('/button/{template}', [ChatbotWizardController::class, 'deleteButton'])->name('delete-button');

            // Flow Management (delete only, create/edit uses existing flow routes)
            Route::delete('/flow/{flow}', [ChatbotWizardController::class, 'deleteFlow'])->name('delete-flow');

            // Product Management
            Route::post('/product', [ChatbotWizardController::class, 'saveProduct'])->name('save-product');
            Route::delete('/product/{product}', [ChatbotWizardController::class, 'deleteProduct'])->name('delete-product');

            //API Integration          
            Route::post('/integrations', [ChatbotWizardController::class, 'saveApiIntegration'])
                ->name('integrations.save');

            Route::delete('/integrations/{key}', [ChatbotWizardController::class, 'deleteApiIntegration'])
                ->name('integrations.delete');

            // Service Management
            Route::post('/service', [ChatbotWizardController::class, 'saveService'])->name('save-service');
            Route::delete('/service/{service}', [ChatbotWizardController::class, 'deleteService'])->name('delete-service');
        });


        // Companies CRUD
        Route::resource('companies', CompanyController::class);
        Route::get('companies/{company}/settings', [CompanySettingsController::class, 'edit'])
            ->name('companies.settings.edit');
        Route::put('companies/{company}/settings', [CompanySettingsController::class, 'update'])
            ->name('companies.settings.update');

        Route::resource('chatbotconfigs', ChatbotConfigController::class);


        // Chatbot Flows Routes 

        Route::prefix('companies/{company}')->group(function () {
            Route::get('flows', [FlowController::class, 'show'])->name('flows.index');
            Route::get('flows/create', [FlowController::class, 'create'])->name('flows.create');
            Route::post('flows', [FlowController::class, 'store'])->name('flows.store');
            Route::get('flows/{flow}/edit', [FlowController::class, 'edit'])->name('flows.edit');
            Route::put('flows/{flow}', [FlowController::class, 'update'])->name('flows.update');
            Route::delete('flows/{flow}', [FlowController::class, 'destroy'])->name('flows.destroy');
        });
    });
});
