<?php

namespace App\Providers;

use App\Services\Branding;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Branding::class);
    }

    public function boot(): void
    {
        if ($rootUrl = config('app.url')) {
            URL::forceRootUrl($rootUrl);
        }

        View::composer(['layouts.*', 'auth.*', 'reports.*', 'settings.*', 'recipients.*', 'categories.*', 'users.*', 'routing.*', 'partials.*'], function ($view) {
            $view->with('branding', app(Branding::class));
            $view->with('microsoftSettings', app(\App\Services\MicrosoftSettings::class));

            if (auth()->check() && str_starts_with($view->name(), 'layouts.')) {
                $user = auth()->user();
                $notifications = \App\Models\ReportEvent::query()
                    ->with('report:id,subject')
                    ->whereIn('type', ['sent', 'approved', 'rejected', 'replied', 'created'])
                    ->whereHas('report', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->orWhereHas('participants', function ($participantQuery) use ($user) {
                                $participantQuery->where('user_id', $user->id)
                                    ->orWhere('email', $user->email);
                            });
                    })
                    ->latest()
                    ->limit(8)
                    ->get();

                $view->with('notifications', $notifications);
            }
        });
    }
}
