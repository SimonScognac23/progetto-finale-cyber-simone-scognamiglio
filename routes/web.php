<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WriterController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\RevisorController;

// Public routes
Route::get('/', [PublicController::class, 'homepage'])->name('homepage');
Route::get('/careers', [PublicController::class, 'careers'])->name('careers');
Route::post('/careers/submit', [PublicController::class, 'careersSubmit'])->name('careers.submit');
Route::get('/articles/index', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/show/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/articles/category/{category}', [ArticleController::class, 'byCategory'])->name('articles.byCategory');
Route::get('/articles/user/{user}', [ArticleController::class, 'byUser'])->name('articles.byUser');

/*
 * CHALLENGE 1 - MITIGAZIONE: Rate Limiter mancante
 *
 * PROBLEMA: La rotta /articles/search era pubblica e senza alcuna protezione.
 * Uno script bash poteva inviare migliaia di richieste consecutive causando
 * un rallentamento o blocco completo del server (attacco DoS).
 *
 * SOLUZIONE: Aggiunto il middleware throttle:10,1 che limita ogni singolo IP
 * a un massimo di 10 richieste al minuto su questa rotta.
 * Se un IP supera il limite, Laravel risponde automaticamente con
 * HTTP 429 Too Many Requests e blocca temporaneamente l'accesso.
 *
 * PRIMA:  Route::get('/articles/search', ...)->name('articles.search');
 * DOPO:   Route::get('/articles/search', ...)->middleware('throttle:10,1')->name('articles.search');
 */
Route::get('/articles/search', [ArticleController::class, 'articleSearch'])->middleware('throttle:10,1')->name('articles.search');

/*
 * CHALLENGE 6 - Rotte per la pagina profilo utente
 * Queste rotte permettono agli utenti loggati di modificare
 * nome, email e password del proprio profilo.
 * Sono volutamente vulnerabili al Mass Assignment Attack
 * per dimostrare l'importanza del fillable nel modello User.
 */
Route::middleware('auth')->group(function(){
    Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});

// Writer routes
Route::middleware('writer')->group(function(){
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles/store', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/writer/dashboard', [WriterController::class, 'dashboard'])->name('writer.dashboard');
    Route::get('/articles/edit/{article}', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/update/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/destroy/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
});

// Revisor routes
Route::middleware('revisor')->group(function(){
    Route::get('/revisor/dashboard', [RevisorController::class, 'dashboard'])->name('revisor.dashboard');
    Route::post('/revisor/{article}/accept', [RevisorController::class, 'acceptArticle'])->name('revisor.acceptArticle');
    Route::post('/revisor/{article}/reject', [RevisorController::class, 'rejectArticle'])->name('revisor.rejectArticle');
    Route::post('/revisor/{article}/undo', [RevisorController::class, 'undoArticle'])->name('revisor.undoArticle');
});

// Admin routes
Route::middleware(['admin','admin.local'])->group(function(){
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    /*
     * CHALLENGE 2 - MITIGAZIONE: Operazioni critiche in GET (CSRF)
     *
     * PROBLEMA: Le rotte set-admin, set-revisor e set-writer erano definite
     * con il metodo GET. Questo e' molto pericoloso perche':
     * 1. Le richieste GET non sono protette dal token CSRF di Laravel
     * 2. Un hacker puo' creare una pagina HTML trappola (es. pagina degli orsi)
     *    che contiene un link nascosto a queste rotte
     * 3. Se un admin visita quella pagina mentre e' loggato, il browser
     *    esegue automaticamente la richiesta GET con i suoi cookie di sessione
     * 4. Il risultato e' una VERTICAL PRIVILEGE ESCALATION: un utente normale
     *    viene promosso ad amministratore senza che l'admin se ne accorga!
     *
     * COME AVVENIVA L'ATTACCO:
     * - La pagina trappola (index.html) mostrava contenuto innocuo (orsetti)
     * - Dopo 5 secondi eseguiva automaticamente: GET /admin/1/set-admin
     * - Poiche' l'admin era loggato, il browser mandava i cookie di sessione
     * - Laravel eseguiva l'azione pensando fosse una richiesta legittima
     * - L'utente con ID 1 diventava amministratore!
     *
     * SOLUZIONE: Cambiare il metodo HTTP da GET a PATCH.
     * PATCH non e' eseguibile tramite semplici link o tag <img>,
     * richiede una richiesta esplicita con il token CSRF di Laravel.
     * Cosi' anche se un hacker crea una pagina trappola, non puo'
     * piu' sfruttare la sessione dell'admin per eseguire queste azioni.
     *
     * PRIMA:  Route::get('/admin/{user}/set-admin', ...)
     * DOPO:   Route::patch('/admin/{user}/set-admin', ...)
     *
     * NOTA: Bisogna anche aggiornare la vista admin per usare un form
     * con method PATCH invece dei semplici link <a href="...">.
     */
    Route::patch('/admin/{user}/set-admin', [AdminController::class, 'setAdmin'])->name('admin.setAdmin');
    Route::patch('/admin/{user}/set-revisor', [AdminController::class, 'setRevisor'])->name('admin.setRevisor');
    Route::patch('/admin/{user}/set-writer', [AdminController::class, 'setWriter'])->name('admin.setWriter');

    Route::put('/admin/edit/tag/{tag}', [AdminController::class, 'editTag'])->name('admin.editTag');
    Route::delete('/admin/delete/tag/{tag}', [AdminController::class, 'deleteTag'])->name('admin.deleteTag');
    Route::put('/admin/edit/category/{category}', [AdminController::class, 'editCategory'])->name('admin.editCategory');
    Route::delete('/admin/delete/category/{category}', [AdminController::class, 'deleteCategory'])->name('admin.deleteCategory');
    Route::post('/admin/category/store', [AdminController::class, 'storeCategory'])->name('admin.storeCategory');
    Route::post('/admin/tag/store', [AdminController::class, 'storeTag'])->name('admin.storeTag');
});