<?php
namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;

class HttpService
{
    protected $client;
    protected $allowedDomains = ['internal.finance', 'newsapi.org'];
    protected $allowedProtocols = ['http', 'https'];
    protected $refererHeader;

    public function __construct()
    {
        $this->refererHeader = config('app.url');
        $this->client = new Client();
    }

    public function getRequest($url)
    {
        $parsedUrl = parse_url($url);

        // Validate protocol
        if (!in_array($parsedUrl['scheme'], $this->allowedProtocols)) {
            return 'Protocol not allowed';
        }

        // Validate domain
        if (!isset($parsedUrl['host']) || !in_array($parsedUrl['host'], $this->allowedDomains)) {
            return 'Domain not allowed';
        }

        /*
         * CHALLENGE 4 - MITIGAZIONE: Manomissione Input (SSRF)
         *
         * PROBLEMA: Anche se internal.finance era nella lista dei domini
         * permessi, qualsiasi utente (anche un writer) poteva fare
         * richieste verso quel dominio manomettendo la select.
         * Questo permetteva di rubare dati finanziari sensibili!
         *
         * SOLUZIONE: Controllo del ruolo per dominio sensibile.
         * Se l'URL punta a internal.finance, solo gli admin
         * possono eseguire la richiesta. Un writer viene bloccato
         * con un messaggio di errore "Not authorized".
         *
         * In questo modo anche se un hacker riesce a modificare
         * l'URL nella select, il server blocca la richiesta
         * perche' il writer non ha i permessi necessari.
         */
        if ($parsedUrl['host'] === 'internal.finance') {
            if (!Auth::check() || !Auth::user()->is_admin) {
                return 'Not authorized: only admins can access financial data';
            }
        }

        // Aggiungi l'intestazione Referer
        $options['headers'] = ['Referer' => $this->refererHeader];

        try {
            $response = $this->client->request('GET', $url, $options);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            return 'Something went wrong: ' . $e->getMessage();
        }
    }
}