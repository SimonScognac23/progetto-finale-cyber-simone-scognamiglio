<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($roleRequests as $user)
            <tr>
                <th scope="row">{{$user->id}}</th>
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>
                <td>
                    @switch($role)
                        @case('admin')
                            {{--
                                ============================================
                                CHALLENGE 2 - MITIGAZIONE: CSRF su GET
                                ============================================

                                PROBLEMA - COSA C'ERA PRIMA:
                                <a href="{{route('admin.setAdmin', $user)}}">Enable admin</a>

                                Questo e' un semplice link HTML che usa il metodo GET.
                                Il metodo GET e' pericolosissimo per operazioni critiche perche':

                                1. NON include il token CSRF di Laravel
                                   (il token CSRF e' un codice segreto che Laravel genera
                                   per ogni sessione e verifica ad ogni richiesta POST/PATCH/PUT.
                                   Con GET questo controllo NON viene fatto!)

                                2. Puo' essere chiamato da QUALSIASI pagina esterna
                                   Un hacker crea una pagina trappola con un link nascosto:
                                   <a href="http://internal.admin:8000/admin/1/set-admin">
                                   Se un admin visita quella pagina mentre e' loggato,
                                   il browser manda automaticamente i cookie di sessione
                                   e Laravel esegue l'azione pensando sia legittima!

                                3. Questo si chiama VERTICAL PRIVILEGE ESCALATION:
                                   un utente normale diventa amministratore
                                   senza che nessuno se ne accorga!

                                SOLUZIONE - COSA ABBIAMO MESSO DOPO:
                                Sostituito il link con un FORM che usa:
                                - method="POST" (l'unico metodo HTML supportato dai form)
                                - @csrf (aggiunge il token segreto di Laravel)
                                - @method('PATCH') (dice a Laravel di trattarlo come PATCH)

                                Cosi' anche se un hacker crea una pagina trappola,
                                non puo' includere il token CSRF valido
                                e Laravel blocca la richiesta con errore 419!
                            --}}
                            <form action="{{route('admin.setAdmin', $user)}}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
                            </form>
                            @break

                        @case('revisor')
                            {{--
                                Stesso fix della Challenge 2 applicato al ruolo revisor.
                                Sostituito link GET con form PATCH + token CSRF.
                            --}}
                            <form action="{{route('admin.setRevisor', $user)}}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
                            </form>
                            @break

                        @case('writer')
                            {{--
                                Stesso fix della Challenge 2 applicato al ruolo writer.
                                Sostituito link GET con form PATCH + token CSRF.
                            --}}
                            <form action="{{route('admin.setWriter', $user)}}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
                            </form>
                            @break

                    @endswitch
                </td>
            </tr>
        @endforeach
    </tbody>
</table>