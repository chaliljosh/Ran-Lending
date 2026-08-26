<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\AdminController;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Ensures every page using admin.layout has the header data
        // (profile dropdown, notification bell) available, even when
        // the controller method rendering that page didn't explicitly
        // pass adminName/notifications/etc. itself. Only fills in
        // MISSING keys — if a controller already passed these (like
        // dashboard() does), those values are kept as-is, not overwritten.
        View::composer('admin.layout', function ($view) {
            $adminId = Session::get('user_id');
            if (!$adminId) {
                return;
            }

            $existingKeys = array_keys($view->getData());
            $headerData = app(AdminController::class)->getHeaderData($adminId);

            $missingData = array_diff_key($headerData, array_flip($existingKeys));

            if (!empty($missingData)) {
                $view->with($missingData);
            }
        });
    }
}