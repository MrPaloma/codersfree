<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

use Illuminate\Support\Facades\Http; # permite hacer peticiones de tipo HTTP

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        // se hace una peticion POST a la api y se guarda los resultados que te da la api del usuario logueado
        $response = Http::withHeaders([
            'Accept' =>'application/json',
        ])->post('http://api.codersfree.test/v1/login', [
            'email' => $request->email, 
            'password' => $request->password
        ]);

        if ($response->status() == 404) {
            return back()->withErrors('Sus credenciales no coinciden');
        }

        $service = $response->json();

        // busca en la bd si existe un email igual, lo actualiza con la data de la api, sino lo encuentra lo crea en la bd del cliente lo crea
        $user = User::updateOrCreate(
            ['email' => $request->email], 
            $service['data']);

        if (!$user->accessToken()->count()) {
            $response = Http::withHeaders([
                'Accept' =>'application/json',
            ])->post('http://api.codersfree.test/oauth/token', [
                'grant_type' => 'password',
                'client_id' => '93d29569-44c6-4e09-83d4-3e7bb6dd4a0a',
                'client_secret' => '5EHs6v9OBnDwGTZOzaJds9Slo5OYP7B1KC5A9YbR',
                'username' => $request->email,
                'password' => $request->password
            ]);
    
            $access_token = $response->json();
    
            $user->accessToken()->create([
                'service_id' => $service['data']['id'],
                'access_token' => $access_token['access_token'],
                'refresh_token' => $access_token['refresh_token'],
                'expires_at' => now()->addSecond($access_token['expires_in'])
            ]);
        }

        Auth::login($user, $request->remember);

        return redirect()->intended(RouteServiceProvider::HOME);
        
        // $request->authenticate();

        // $request->session()->regenerate();

        // return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
