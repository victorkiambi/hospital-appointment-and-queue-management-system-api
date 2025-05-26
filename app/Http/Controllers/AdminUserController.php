<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;

class AdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$request->user() || $request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Forbidden',
                    'errors' => ['auth' => ['Admin access required']],
                ], 403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $users = User::paginate($perPage);
        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
            'message' => 'Users fetched successfully',
            'errors' => null,
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'errors' => ['id' => ['User not found']],
            ], 404);
        }
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User fetched successfully',
            'errors' => null,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully',
            'errors' => null,
        ], 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'errors' => ['id' => ['User not found']],
            ], 404);
        }
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User updated successfully',
            'errors' => null,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'errors' => ['id' => ['User not found']],
            ], 404);
        }
        $user->delete();
        return response()->json([
            'data' => null,
            'message' => 'User deleted successfully',
            'errors' => null,
        ]);
    }

    public function storeDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'specialization' => 'required|string|max:255',
            'availability' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'doctor',
            ]);
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization' => $request->specialization,
                'availability' => $request->availability,
            ]);
            return response()->json([
                'data' => [
                    'user' => $user,
                    'doctor' => $doctor,
                ],
                'message' => 'Doctor user and profile created successfully',
                'errors' => null,
            ], 201);
        });
    }
} 