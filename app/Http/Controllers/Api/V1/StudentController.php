<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'external_id' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'cpf' => 'nullable|string|max:14',
            'moodle_user_id' => 'nullable|string|max:100',
        ]);

        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $student = User::updateOrCreate(
            [
                'client_system_id' => $clientSystem->id,
                'external_id' => $validated['external_id'],
            ],
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'cpf' => $validated['cpf'] ?? null,
                'moodle_user_id' => $validated['moodle_user_id'] ?? null,
                'role' => 'student',
                'password' => Hash::make(Str::random(32)),
                'active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'external_id' => $student->external_id,
                'name' => $student->name,
                'email' => $student->email,
            ],
        ], $student->wasRecentlyCreated ? 201 : 200);
    }

    public function bulkUpsert(Request $request): JsonResponse
    {
        $request->validate([
            'students' => 'required|array|min:1|max:500',
            'students.*.external_id' => 'required|string|max:100',
            'students.*.name' => 'required|string|max:255',
            'students.*.email' => 'required|email|max:255',
            'students.*.cpf' => 'nullable|string|max:14',
        ]);

        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $created = 0;
        $updated = 0;

        foreach ($request->students as $data) {
            $student = User::updateOrCreate(
                [
                    'client_system_id' => $clientSystem->id,
                    'external_id' => $data['external_id'],
                ],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'cpf' => $data['cpf'] ?? null,
                    'role' => 'student',
                    'password' => Hash::make(Str::random(32)),
                    'active' => true,
                ]
            );

            $student->wasRecentlyCreated ? $created++ : $updated++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'created' => $created,
                'updated' => $updated,
                'total' => $created + $updated,
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $student = User::where('id', $id)
            ->where('client_system_id', $clientSystem->id)
            ->where('role', 'student')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'external_id' => $student->external_id,
                'name' => $student->name,
                'email' => $student->email,
                'cpf' => $student->cpf,
                'moodle_user_id' => $student->moodle_user_id,
                'active' => $student->active,
            ],
        ]);
    }
}
