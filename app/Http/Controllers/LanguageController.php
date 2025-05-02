<?php

namespace App\Http\Controllers;

use App\Models\Language;

class LanguageController extends Controller
{
    public function index()
    {
        return response()->json(Language::all(), 200);
    }
}

