<?php

// Commentaire d'intention: valide les donnees d'inscription selon le role choisi.

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * L'inscription est publique, donc aucune session prealable n'est requise.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regles de validation alignees sur users et profils_etudiants.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:etudiant,conseiller'],
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'string', 'max:20', 'unique:users,telephone'],
            'specialite' => ['required_if:role,conseiller', 'nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'conditions_acceptees' => ['accepted'],
            'langue_preferee' => ['nullable', 'in:fr,en'],
        ];
    }
}
