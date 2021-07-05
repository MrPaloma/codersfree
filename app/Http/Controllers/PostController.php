<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http; # permite hacer peticiones de tipo HTTP

class PostController extends Controller
{
    public function store()
    {
        
        Http::withHeaders([
            'Accept' => 'aplicaction/json',
            'Autorizathion' => 'Bearer ' . auth()->user()->accessToken->access_token,
        ])->post('http://api.codersfree.test/v1/posts', [

        ]);
    }
}
