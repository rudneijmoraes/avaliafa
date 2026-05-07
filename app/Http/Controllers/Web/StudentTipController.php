<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TeacherTip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StudentTipController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $student = auth()->user();

        if (! $student || ! $student->isStudent()) {
            return redirect()->route('dashboard');
        }

        $tips = TeacherTip::with('simulado')->active()->forSystem($student->client_system_id)->ordered()->get();

        return view('student.tips.index', ['tips' => $tips]);
    }
}
