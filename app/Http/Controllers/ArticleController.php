<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class ArticleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth', except: ['index', 'show', 'byCategory', 'byUser', 'articleSearch']),
        ];
    }

    public function index()
    {
        $articles = Article::where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.index', compact('articles'));
    }

    public function create()
    {
        return view('articles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|unique:articles|min:5',
            'subtitle' => 'required|min:5',
            'body' => 'required|min:10',
            'image' => 'required|image',
            'category' => 'required',
            'tags' => 'required'
        ]);

        /*
         * CHALLENGE 5 - MITIGAZIONE: Validazione contenuto articolo non presente
         *
         * PROBLEMA: Il body dell'articolo veniva salvato nel database
         * esattamente come arrivava dalla richiesta HTTP, senza alcuna
         * sanitizzazione. Usando BurpSuite come proxy, un hacker poteva
         * intercettare la richiesta POST e modificare il campo body
         * inserendo uno script malevolo come:
         * <script>alert('XSS hacked!')</script>
         *
         * Questo script veniva salvato nel database e poi eseguito
         * ogni volta che un utente visualizzava l'articolo infettato.
         * Questo si chiama STORED XSS (Cross-Site Scripting) ed e' il
         * tipo piu' pericoloso perche' colpisce TUTTI gli utenti che
         * leggono quell'articolo, non solo uno.
         *
         * Con uno script piu' complesso l'hacker poteva rubare i cookie
         * di sessione degli utenti e impersonarli sul sito!
         *
         * SOLUZIONE: Sanitizzazione del body con strip_tags()
         * La funzione strip_tags() rimuove tutti i tag HTML non presenti
         * nella whitelist. I tag nella whitelist sono quelli necessari
         * per la formattazione normale di un articolo (grassetto, corsivo,
         * titoli, liste, link, immagini). I tag pericolosi come
         * <script>, <iframe>, <object> vengono rimossi automaticamente.
         *
         * PRIMA: 'body' => $request->body  (pericoloso!)
         * DOPO:  'body' => strip_tags($request->body, $allowedTags)
         */
        $allowedTags = '<p><b><i><u><h1><h2><h3><ul><ol><li><a><img><strong><em><br>';
        $sanitizedBody = strip_tags($request->body, $allowedTags);

        $article = Article::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'body' => $sanitizedBody,
            'image' => $request->file('image')->store('public/images'),
            'category_id' => $request->category,
            'user_id' => Auth::user()->id,
            'slug' => Str::slug($request->title),
        ]);

        /*
         * CHALLENGE 3 - MITIGAZIONE: Log mancanti per operazioni critiche
         *
         * PROBLEMA: Senza log non si sapeva chi aveva creato, modificato
         * o eliminato un articolo. In caso di contenuto malevolo pubblicato
         * non si poteva risalire al responsabile.
         *
         * SOLUZIONE: Log::info() registra ogni operazione critica con:
         * - Chi l'ha eseguita (user_id e nome)
         * - Su quale risorsa (article_id e titolo)
         * - Quando (timestamp automatico di Laravel)
         * I log vengono salvati in storage/logs/laravel.log
         */
        Log::info('ARTICOLO - Creazione', [
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name,
            'article_id' => $article->id,
            'article_title' => $article->title,
            'action' => 'create',
        ]);

        $tags = explode(',', $request->tags);
        foreach($tags as $i => $tag){
            $tags[$i] = trim($tag);
        }
        foreach($tags as $tag){
            $newTag = Tag::updateOrCreate([
                'name' => strtolower($tag)
            ]);
            $article->tags()->attach($newTag);
        }

        return redirect(route('homepage'))->with('message', 'Articolo creato con successo');
    }

    public function show(Article $article)
    {
        return view('articles.show', compact('article'));
    }

    public function edit(Article $article)
    {
        if(Auth::user()->id != $article->user_id){
            return redirect()->route('homepage')->with('alert', 'Accesso non consentito');
        }
        return view('articles.edit', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $request->validate([
            'title' => 'required|min:5|unique:articles,title,' . $article->id,
            'subtitle' => 'required|min:5',
            'body' => 'required|min:10',
            'image' => 'image',
            'category' => 'required',
            'tags' => 'required'
        ]);

        /*
         * CHALLENGE 5 - MITIGAZIONE: Sanitizzazione anche in fase di modifica
         * Stesso fix applicato al metodo update() per proteggere
         * anche la modifica degli articoli esistenti.
         */
        $allowedTags = '<p><b><i><u><h1><h2><h3><ul><ol><li><a><img><strong><em><br>';
        $sanitizedBody = strip_tags($request->body, $allowedTags);

        $article->update([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'body' => $sanitizedBody,
            'category_id' => $request->category,
            'slug' => Str::slug($request->title),
        ]);

        if($request->image){
            Storage::delete($article->image);
            $article->update([
                'image' => $request->file('image')->store('public/images')
            ]);
        }

        $tags = explode(',', $request->tags);
        foreach($tags as $i => $tag){
            $tags[$i] = trim($tag);
        }
        $newTags = [];
        foreach($tags as $tag){
            $newTag = Tag::updateOrCreate([
                'name' => strtolower($tag)
            ]);
            $newTags[] = $newTag->id;
        }
        $article->tags()->sync($newTags);

        // CHALLENGE 3 - Log modifica articolo
        Log::info('ARTICOLO - Modifica', [
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name,
            'article_id' => $article->id,
            'article_title' => $article->title,
            'action' => 'update',
        ]);

        return redirect(route('writer.dashboard'))->with('message', 'Articolo modificato con successo');
    }

    public function destroy(Article $article)
    {
        // CHALLENGE 3 - Log eliminazione articolo
        Log::info('ARTICOLO - Eliminazione', [
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name,
            'article_id' => $article->id,
            'article_title' => $article->title,
            'action' => 'delete',
        ]);

        foreach ($article->tags as $tag) {
            $article->tags()->detach($tag);
        }
        $article->delete();

        return redirect()->back()->with('message', 'Articolo cancellato con successo');
    }

    public function byCategory(Category $category){
        $articles = $category->articles()->where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.by-category', compact('category', 'articles'));
    }

    public function byUser(User $user){
        $articles = $user->articles()->where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.by-user', compact('user', 'articles'));
    }

    public function articleSearch(Request $request){
        $query = $request->input('query');
        $articles = Article::search($query)->where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.search-index', compact('articles', 'query'));
    }
}