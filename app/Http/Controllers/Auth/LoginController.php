<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Sobrescribimos el método para determinar el campo de login.
     */
    public function username()
    {
        $login = request()->input('login');

        // Verificamos si es un email, de lo contrario asumimos que es username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Fusionamos el valor con el request para que Laravel encuentre la credencial correcta
        request()->merge([$field => $login]);

        return $field;
    }

    /**
     * Sobrescribimos la validación para que no busque 'email' obligatoriamente.
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);
    }
}