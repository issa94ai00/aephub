<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminUserController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'role' => ['nullable', 'in:student,teacher,admin'],
            'status' => ['nullable', 'in:active,frozen'],
        ]);

        $limit = (int) ($data['limit'] ?? 10);
        $needle = trim((string) $data['q']);

        $q = User::query()
            ->select(['id', 'name', 'email', 'role', 'status'])
            ->where(function ($sub) use ($needle) {
                $sub->where('name', 'like', "%{$needle}%")
                    ->orWhere('email', 'like', "%{$needle}%");
            })
            ->latest('id')
            ->limit($limit);

        if (!empty($data['role'])) {
            $q->where('role', $data['role']);
        }
        if (!empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        $users = $q->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'status' => $u->status ?? 'active',
        ]);

        return response()->json(['data' => $users]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:student,teacher,admin'],
            'status' => ['nullable', 'in:active,frozen'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = User::query()->select([
            'id',
            'name',
            'email',
            'role',
            'status',
            'account_lock_id',
            'frozen_at',
            'teacher_approval_status',
        ])->latest('id');

        if (!empty($data['role'])) {
            $q->where('role', $data['role']);
        }
        if (!empty($data['status'])) {
            $q->where('status', $data['status']);
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

    public function freezeByName(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'lock_id' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $needle = trim((string) $data['user_name']);

        $matches = User::query()
            ->where('name', $needle)
            ->orWhere('email', $needle)
            ->select(['id', 'name', 'email', 'status', 'account_lock_id', 'frozen_at'])
            ->limit(5)
            ->get();

        if ($matches->count() === 0) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($matches->count() > 1) {
            return response()->json([
                'message' => 'Ambiguous user_name; multiple users match. Use /admin/users/suggest then freeze by id.',
                'matches' => $matches->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ])->values()->all(),
            ], 422);
        }

        /** @var User $user */
        $user = $matches->first();

        return $this->freeze($request, $user);
    }

    public function freeze(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'lock_id' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $user->forceFill([
            'status' => 'frozen',
            'account_lock_id' => $data['lock_id'],
            'frozen_at' => Carbon::now(),
        ])->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'status' => $user->status,
                'account_lock_id' => $user->account_lock_id,
                'frozen_at' => optional($user->frozen_at)->toISOString(),
            ],
        ]);
    }

    public function unfreeze(User $user): JsonResponse
    {
        $user->forceFill([
            'status' => 'active',
            'account_lock_id' => null,
            'frozen_at' => null,
        ])->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'status' => $user->status,
                'account_lock_id' => $user->account_lock_id,
            ],
        ]);
    }
}

