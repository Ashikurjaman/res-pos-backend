// app/Http/Controllers/AuthController.php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ==================== AUTHENTICATION ====================
    
    public function signup(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|unique:users|min:3|max:20',
                'email' => 'nullable|email|unique:users',
                'password' => 'required|string|min:6',
                'firstName' => 'required|string|max:50',
                'lastName' => 'required|string|max:50',
            ]);

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'role' => 'user',
                'status' => 'active',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function signin(Request $request)
    {
        try {
            $validated = $request->validate([
                'usernameOrEmail' => 'required|string',
                'password' => 'required|string',
            ]);

            $field = filter_var($validated['usernameOrEmail'], FILTER_VALIDATE_EMAIL) 
                ? 'email' 
                : 'username';

            if (!Auth::attempt([$field => $validated['usernameOrEmail'], 'password' => $validated['password']])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $user = User::where($field, $validated['usernameOrEmail'])->firstOrFail();

            // Check if user is active
            if ($user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is ' . $user->status . '. Please contact support.',
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function signout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'user' => $request->user(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed',
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== USER MANAGEMENT (CRUD) ====================

    // Get all users (Admin only)
    public function index(Request $request)
    {
        try {
            $users = User::when($request->search, function ($query, $search) {
                return $query->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->when($request->role, function ($query, $role) {
                return $query->where('role', $role);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get single user
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    // Create user (Admin only)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|unique:users|min:3|max:20',
                'email' => 'nullable|email|unique:users',
                'password' => 'required|string|min:6',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'role' => 'sometimes|in:admin,user',
                'status' => 'sometimes|in:active,inactive,banned',
            ]);

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'role' => $validated['role'] ?? 'user',
                'status' => $validated['status'] ?? 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'username' => 'sometimes|string|unique:users,username,' . $id . '|min:3|max:20',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:6',
                'first_name' => 'sometimes|string|max:50',
                'last_name' => 'sometimes|string|max:50',
                'role' => 'sometimes|in:admin,user',
                'status' => 'sometimes|in:active,inactive,banned',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting self
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update user status
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,inactive,banned',
            ]);

            $user = User::findOrFail($id);

            // Prevent changing own status
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change your own status',
                ], 403);
            }

            $user->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update user role
    public function updateRole(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|in:admin,user',
            ]);

            $user = User::findOrFail($id);

            // Prevent changing own role
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change your own role',
                ], 403);
            }

            $user->update(['role' => $validated['role']]);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Bulk delete users
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:users,id',
            ]);

            // Prevent deleting self
            $ids = array_filter($validated['ids'], function ($id) {
                return auth()->id() !== $id;
            });

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                ], 403);
            }

            User::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Users deleted successfully',
                'deleted_count' => count($ids),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}