<?php

namespace App\Http\Controllers;

class TeacherController extends Controller
{
    public function dashboard()
    {
        return view('dashboard.teacher');
    }
}
