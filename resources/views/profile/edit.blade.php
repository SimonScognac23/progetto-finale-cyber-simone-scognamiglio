<x-layout>
    <div class="container-fluid p-5 bg-secondary-subtle text-center">
        <div class="row justify-content-center">
            <div class="col-12">
                <h1 class="display-1">Edit Profile</h1>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">

                @if(session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif

                {{--
                    CHALLENGE 6 - VULNERABILITA': Mass Assignment Attack
                    
                    PROBLEMA: Questo form e' volutamente vulnerabile.
                    Un utente malintenzionato puo' aggiungere campi nascosti
                    come is_admin=1 usando "Ispeziona elemento" del browser.
                    Poiche' il modello User ha is_admin nel fillable,
                    il campo viene accettato e salvato nel database!
                    Questo porta a una PRIVILEGE ESCALATION.
                --}}
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" name="name" id="name"
                            class="form-control" value="{{ $user->name }}">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email"
                            class="form-control" value="{{ $user->email }}">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nuova Password</label>
                        <input type="password" name="password"
                            id="password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Conferma Password</label>
                        <input type="password" name="password_confirmation"
                            id="password_confirmation" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Salva modifiche
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-layout>