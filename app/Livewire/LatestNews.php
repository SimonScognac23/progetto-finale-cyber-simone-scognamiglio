<?php
namespace App\Livewire;
use Livewire\Component;
use App\Services\HttpService;
use Illuminate\Support\Facades\Auth;

class LatestNews extends Component
{
    public $selectedApi;
    public $news;
    protected $httpService;

    /*
     * CHALLENGE 4 - MITIGAZIONE: Manomissione Input (SSRF)
     *
     * PROBLEMA: La funzione fetchNews() accettava qualsiasi URL
     * dalla select, incluso http://internal.finance:8001/user-data.php
     * Un writer poteva modificare il value della select dal browser
     * e far fare al server una richiesta verso risorse interne
     * non accessibili direttamente dall'esterno (SSRF Attack).
     * Cosi' otteneva dati finanziari sensibili che non dovrebbe vedere!
     *
     * SOLUZIONE 1: Whitelist degli URL permessi
     * Solo gli URL presenti in questa lista sono accettati.
     * Qualsiasi altro URL viene rifiutato con errore.
     *
     * SOLUZIONE 2: Controllo del ruolo
     * Spostato in HttpService.php — anche se l'URL fosse valido,
     * solo gli admin possono fare richieste a internal.finance.
     */
    protected array $allowedUrls = [
        'https://newsapi.org/v2/top-headlines?country=it&apiKey=5fbe92849d5648eabcbe072a1cf91473',
        'https://newsapi.org/v2/top-headlines?country=gb&apiKey=5fbe92849d5648eabcbe072a1cf91473',
        'https://newsapi.org/v2/top-headlines?country=us&apiKey=5fbe92849d5648eabcbe072a1cf91473',
    ];

    public function __construct()
    {
        $this->httpService = app(HttpService::class);
    }

    public function fetchNews()
    {
        // Controlla che l'URL sia nella whitelist
        if (!in_array($this->selectedApi, $this->allowedUrls)) {
            $this->news = 'URL non autorizzato!';
            return;
        }

        // Controlla che sia un URL valido
        if (filter_var($this->selectedApi, FILTER_VALIDATE_URL) === FALSE) {
            $this->news = 'URL non valido!';
            return;
        }

        $this->news = json_decode($this->httpService->getRequest($this->selectedApi), true);
    }

    public function render()
    {
        return view('livewire.latest-news');
    }
}