<?php

namespace App\Providers;

use App\Models\Candidates;
use App\Models\JobCandidates;
use App\Models\JobOpenings;
use App\Observers\CandidatesObserver;
use App\Observers\JobCandidatesObserver;
use App\Observers\JobOpeningsObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        JobCandidates::observe(JobCandidatesObserver::class);
        Candidates::observe(CandidatesObserver::class);
        JobOpenings::observe(JobOpeningsObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
