<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'nullable|min:8|confirmed',
        ]);

        /*
         * CHALLENGE 6 - VULNERABILITA': Mass Assignment
         *
         * PROBLEMA: Stiamo usando update() con tutti i dati della request.
         * Se un utente aggiunge campi extra come is_admin=true nel form
         * (tramite ispeziona elemento), questi vengono passati direttamente
         * al modello e salvati nel database perche' is_admin e' nel fillable!
         * Questo si chiama MASS ASSIGNMENT ATTACK e porta a
         * PRIVILEGE ESCALATION — un utente normale diventa admin!
         */
        $user = Auth::user();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->is_admin,
            'is_writer' => $request->is_writer,
            'is_revisor' => $request->is_revisor,
        ]);

        if($request->password){
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->back()->with('message', 'Profilo aggiornato con successo!');
    }
}