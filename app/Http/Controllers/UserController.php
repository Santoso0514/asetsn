<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    function index()
    {
        return view('welcome');
    }

    function user()
    {
        return view('welcome');
    }

    function admin()
    {
        return redirect('/dashboard');
    }
}
