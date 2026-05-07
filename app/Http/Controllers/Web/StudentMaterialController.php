<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StudentMaterialController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $student = auth()->user();

        if (! $student || ! $student->isStudent()) {
            return redirect()->route('dashboard');
        }

        $materials = LearningMaterial::active()->forSystem($student->client_system_id)->orderByDesc('created_at')->paginate(12);

        return view('student.materials.index', ['materials' => $materials]);
    }
}
