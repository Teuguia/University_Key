<?php

// Commentaire d'intention: valide les identifiants fournis au formulaire de connexion.

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * La connexion est publique, donc aucune session prealable n'est requise.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Le champ email accepte aussi le telephone pour suivre le formulaire front.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ];
    }
}
