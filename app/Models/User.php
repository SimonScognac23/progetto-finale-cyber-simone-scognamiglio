<?php
namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /*
     * CHALLENGE 6 - MITIGAZIONE: Uso non corretto della proprieta' fillable
     *
     * PROBLEMA: Nel fillable erano presenti is_admin, is_revisor e is_writer.
     * Questi campi NON devono essere modificabili dagli utenti tramite form!
     * Un utente malintenzionato poteva aggiungere un campo nascosto nel form
     * del profilo usando "Ispeziona elemento" del browser:
     * <input type="hidden" name="is_admin" value="1">
     * Quando il form veniva inviato, Laravel accettava il campo is_admin
     * perche' era nel fillable e lo salvava nel database.
     * Risultato: un semplice utente diventava amministratore!
     * Questo si chiama MASS ASSIGNMENT ATTACK.
     *
     * SOLUZIONE: Rimuovere is_admin, is_revisor e is_writer dal fillable.
     * Il fillable deve contenere SOLO i campi che gli utenti possono
     * modificare autonomamente tramite i form dell'applicazione.
     * I campi sensibili come i ruoli devono essere modificati SOLO
     * tramite metodi dedicati con controlli di autorizzazione appropriati
     * (es. solo un admin puo' cambiare i ruoli degli utenti).
     *
     * PRIMA (vulnerabile):
     * protected $fillable = ['name', 'email', 'password', 'is_admin', 'is_revisor', 'is_writer'];
     *
     * DOPO (sicuro):
     * protected $fillable = ['name', 'email', 'password'];
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function articles(){
        return $this->hasMany(Article::class);
    }
}