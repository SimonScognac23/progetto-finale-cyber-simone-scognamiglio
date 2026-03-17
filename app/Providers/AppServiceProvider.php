<?php
namespace App\Providers;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Failed;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if(Schema::hasTable('categories')){
            $categories = Category::all();
            View::share(['categories' => $categories]);
        }
        if(Schema::hasTable('tags')){
            $tags = Tag::all();
            View::share(['tags' => $tags]);
        }

        /*
         * CHALLENGE 3 - MITIGAZIONE: Log mancanti per operazioni critiche
         *
         * PROBLEMA: Non c'era nessun log per login, logout e registrazione.
         * In caso di attacco brute force o accesso non autorizzato
         * non si poteva risalire a chi aveva tentato di accedere
         * e quando, violando i principi di accountability e non-repudiation.
         *
         * SOLUZIONE: Aggiunto listener sugli eventi di autenticazione di Laravel.
         * Laravel genera automaticamente questi eventi:
         * - Login: quando un utente accede con successo
         * - Logout: quando un utente esce
         * - Registered: quando un nuovo utente si registra
         * - Failed: quando un tentativo di login fallisce (utile per brute force)
         *
         * I log vengono salvati in storage/logs/laravel.log
         */

        // Log quando un utente fa LOGIN con successo
        Event::listen(Login::class, function($event) {
            Log::info('AUTENTICAZIONE - Login riuscito', [
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'action' => 'login',
            ]);
        });

        // Log quando un utente fa LOGOUT
        Event::listen(Logout::class, function($event) {
            Log::info('AUTENTICAZIONE - Logout', [
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'action' => 'logout',
            ]);
        });

        // Log quando un nuovo utente si REGISTRA
        Event::listen(Registered::class, function($event) {
            Log::info('AUTENTICAZIONE - Nuova registrazione', [
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'action' => 'register',
            ]);
        });

        // Log quando un tentativo di login FALLISCE
        Event::listen(Failed::class, function($event) {
            Log::warning('AUTENTICAZIONE - Tentativo di login fallito', [
                'email_tentato' => $event->credentials['email'] ?? 'sconosciuto',
                'action' => 'login_failed',
            ]);
        });
    }
}