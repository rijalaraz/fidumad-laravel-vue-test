<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OperationController extends Controller
{
    const API_URL = 'https://agrcf.lib.id/exercice@dev/';
    
    public function getList(Request $request)
    {
        $response = Http::get(self::API_URL);
        $operations = $response->json();
        return response()->json($operations);
    }
}
