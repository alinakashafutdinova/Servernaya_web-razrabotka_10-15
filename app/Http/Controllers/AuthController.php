<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.signin');
    }

    public function registration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ]);

        return response()->json([
            'message' => 'Данные успешно прошли проверку',
            'user' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => '[скрыт]',
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
