<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminTeacherController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = User::query()
            ->where('role', 'teacher')
            ->where('teacher_approval_status', User::TEACHER_APPROVAL_PENDING)
            ->select(['id', 'name', 'email', 'teacher_approval_status', 'created_at'])
            ->latest('id');

        if (!empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $q->where(function ($sub) use ($needle) {
                $sub->where('name', 'like', "%{$needle}%")
                    ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        $p = $q->paginate($perPage);

        $items = $p->getCollection()->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'status' => 'pending',
                'created_at' => optional($u->created_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function approve(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if (strtolower((string) $user->role) !== 'teacher') {
            return response()->json(['message' => 'Not a teacher'], 422);
        }

        $user->forceFill([
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ])->save();

        return response()->json([
            'teacher' => [
                'id' => $user->id,
                'status' => 'approved',
                'teacher_verified_at' => Carbon::now()->toISOString(),
            ],
        ]);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if (strtolower((string) $user->role) !== 'teacher') {
            return response()->json(['message' => 'Not a teacher'], 422);
        }

        $user->forceFill([
            'teacher_approval_status' => User::TEACHER_APPROVAL_REJECTED,
        ])->save();

        return response()->json([
            'teacher' => [
                'id' => $user->id,
                'status' => 'rejected',
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:approved,pending,rejected'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = User::query()
            ->where('role', 'teacher')
            ->select(['id', 'name', 'email', 'teacher_approval_status'])
            ->latest('id');

        if (!empty($data['status'])) {
            $q->where('teacher_approval_status', $data['status']);
        }

        if (!empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $q->where(function ($sub) use ($needle) {
                $sub->where('name', 'like', "%{$needle}%")
                    ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        $p = $q->paginate($perPage);
        return response()->json(ApiPagination::format($p));
    }
}

