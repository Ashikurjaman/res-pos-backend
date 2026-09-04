<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
                'outlet_id' => 'nullable|exists:outlets,id',
                'role' => 'sometimes|exists:roles,name',
            ]);

            $user = \DB::transaction(function () use ($validated) {
                $userData = [
                    'username' => $validated['username'],
                    'email' => $validated['email'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'first_name' => $validated['firstName'],
                    'last_name' => $validated['lastName'],
                    'status' => User::STATUS_ACTIVE,
                ];

                // Only add outlet_id if the column exists
                if (\Schema::hasColumn('users', 'outlet_id')) {
                    $userData['outlet_id'] = $validated['outlet_id'] ?? null;
                }

                $user = User::create($userData);
                $user->assignRole($validated['role'] ?? 'user');

                return $user;
            });

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $this->formatUser($user),
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Signup failed', ['error' => $e->getMessage()]);
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

            if ($user->status !== User::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is ' . $user->status . '. Please contact support.',
                ], 403);
            }

            // Revoke existing tokens
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->formatUser($user),
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Signin failed', ['error' => $e->getMessage()]);
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
            Log::error('Signout failed', ['error' => $e->getMessage()]);
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
                'user' => $this->formatUser($request->user()),
            ]);
        } catch (\Exception $e) {
            Log::error('Get me failed', ['error' => $e->getMessage()]);
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

            // Revoke current token
            $user->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed',
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Refresh token failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== USER MANAGEMENT (CRUD) ====================

    public function index(Request $request)
    {
        try {
            $query = User::with(['outlet', 'roles', 'permissions']);

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            // Filter by role (Spatie scope)
            if ($request->role) {
                $query->role($request->role);
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Filter by outlet
            if ($request->outlet_id && \Schema::hasColumn('users', 'outlet_id')) {
                $query->where('outlet_id', $request->outlet_id);
            }

            // Sort
            $sortBy = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($request->per_page ?? 15);

            // Format users
            $users->getCollection()->transform(function ($user) {
                return $this->formatUser($user);
            });

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('Fetch users failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['outlet', 'roles', 'permissions'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $this->formatUser($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Show user failed', ['user_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|unique:users|min:3|max:20',
                'email' => 'nullable|email|unique:users',
                'password' => 'required|string|min:6',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'role' => 'required|exists:roles,name',
                'status' => 'sometimes|in:' . implode(',', array_keys(User::getStatuses())),
                'outlet_id' => 'nullable|exists:outlets,id',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            $userData = [
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'status' => $validated['status'] ?? User::STATUS_ACTIVE,
            ];

            // Only add outlet_id if column exists
            if (\Schema::hasColumn('users', 'outlet_id')) {
                $userData['outlet_id'] = $validated['outlet_id'] ?? null;
            }

            $user = User::create($userData);

            // Assign role
            $user->assignRole($validated['role']);

            // Extra direct permissions on top of role (optional)
            if (!empty($validated['permissions'])) {
                $user->givePermissionTo($validated['permissions']);
            }

            $user->load(['outlet', 'roles', 'permissions']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $this->formatUser($user),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Store user failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            Log::info('User update request', [
                'user_id' => $id,
                'request_data' => $request->except(['password']),
                'auth_user_id' => auth()->id()
            ]);

            $validated = $request->validate([
                'username' => 'sometimes|string|unique:users,username,' . $id . '|min:3|max:20',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:6',
                'first_name' => 'sometimes|string|max:50',
                'last_name' => 'sometimes|string|max:50',
                'role' => 'sometimes|exists:roles,name',
                'status' => 'sometimes|in:' . implode(',', array_keys(User::getStatuses())),
                'outlet_id' => 'nullable|exists:outlets,id',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            // ✅ Allow superadmin to change their own role (for testing)
            // Remove this in production if you don't want it
            if (auth()->id() === $user->id) {
                // Only allow if user is superadmin or allow for testing
                if (auth()->user()->isSuperAdmin()) {
                    Log::warning('Superadmin changing their own role', ['user_id' => $id]);
                } else {
                    unset($validated['role'], $validated['status']);
                    Log::info('Removed role/status for self-update', ['user_id' => $id]);
                }
            }

            // Handle password
            if (isset($validated['password']) && !empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            // Extract role and permissions before updating
            $role = $validated['role'] ?? null;
            $permissions = $validated['permissions'] ?? null;
            unset($validated['role'], $validated['permissions']);

            // Handle outlet_id
            if (isset($validated['outlet_id']) && !\Schema::hasColumn('users', 'outlet_id')) {
                unset($validated['outlet_id']);
            }

            Log::info('Updating user with data', [
                'user_id' => $id,
                'update_data' => $validated,
                'role_to_assign' => $role,
                'permissions_to_assign' => $permissions
            ]);

            // Update user basic info
            $user->update($validated);

            // Update role via Spatie
            if ($role) {
                $roleExists = Role::where('name', $role)->exists();
                Log::info('Role exists check', ['role' => $role, 'exists' => $roleExists]);

                if ($roleExists) {
                    $user->syncRoles([$role]);
                    Log::info('Role synced successfully', ['user_id' => $id, 'role' => $role]);
                } else {
                    Log::warning('Role not found', ['role' => $role]);
                }
            }

            // Update permissions if provided
            if ($permissions !== null) {
                $user->syncPermissions($permissions);
                Log::info('Permissions synced', ['user_id' => $id, 'permissions' => $permissions]);
            }

            // Refresh user with all relations
            $user->refresh();
            $user->load(['outlet', 'roles', 'permissions']);

            Log::info('User after update', [
                'user_id' => $id,
                'new_role' => $user->getRoleNames()->first(),
                'all_roles' => $user->getRoleNames()->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $this->formatUser($user),
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation error in user update', [
                'user_id' => $id,
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                ], 403);
            }

            // Prevent deleting last super admin
            if ($user->isSuperAdmin() && User::role(User::ROLE_SUPER_ADMIN)->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last super admin',
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('User delete failed', ['user_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:' . implode(',', array_keys(User::getStatuses())),
            ]);

            $user = User::findOrFail($id);

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
                'data' => $this->formatUser($user->fresh(['outlet', 'roles', 'permissions'])),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update status failed', ['user_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateRole(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|exists:roles,name',
            ]);

            $user = User::findOrFail($id);

            Log::info('Role update request', [
                'user_id' => $id,
                'role' => $validated['role'],
                'auth_user_id' => auth()->id()
            ]);

            // ✅ Allow superadmin to change their own role (for testing)
            if (auth()->id() === $user->id) {
                // Only allow if user is superadmin or for testing
                if (auth()->user()->isSuperAdmin()) {
                    Log::warning('Superadmin changing their own role', ['user_id' => $id]);
                    // Allow it
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot change your own role',
                    ], 403);
                }
            }

            // Check if trying to change last super admin
            if ($user->isSuperAdmin()) {
                $superAdminCount = User::role(User::ROLE_SUPER_ADMIN)->count();
                if ($superAdminCount <= 1 && $validated['role'] !== User::ROLE_SUPER_ADMIN) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot change the last super admin role',
                    ], 403);
                }
            }

            // Sync role
            $user->syncRoles([$validated['role']]);

            // Refresh and return
            $user->refresh();
            $user->load(['outlet', 'roles', 'permissions']);

            Log::info('Role updated successfully', [
                'user_id' => $id,
                'new_role' => $validated['role'],
                'user_roles' => $user->getRoleNames()->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => $this->formatUser($user),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Role update failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePermissions(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            $user = User::findOrFail($id);

            if (auth()->id() === $user->id && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change your own permissions',
                ], 403);
            }

            $user->syncPermissions($validated['permissions']);

            return response()->json([
                'success' => true,
                'message' => 'User permissions updated successfully',
                'data' => $this->formatUser($user->fresh(['outlet', 'roles', 'permissions'])),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update permissions failed', ['user_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:users,id',
            ]);

            $ids = array_filter($validated['ids'], function ($id) {
                return auth()->id() !== $id;
            });

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                ], 403);
            }

            // Prevent deleting all super admins
            $superAdminIds = User::role(User::ROLE_SUPER_ADMIN)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->toArray();

            if (count($superAdminIds) > 0) {
                $remainingSuperAdmins = User::role(User::ROLE_SUPER_ADMIN)
                    ->whereNotIn('id', $ids)
                    ->count();

                if ($remainingSuperAdmins === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete all super admins',
                    ], 403);
                }
            }

            User::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Users deleted successfully',
                'deleted_count' => count($ids),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== HELPER METHODS ====================

    private function formatUser($user)
    {
        if (!$user) return null;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'roles' => $user->getRoleNames(),
            'role' => $user->getRoleNames()->first(),
            'status' => $user->status,
            'status_label' => $user->status_label,
            'outlet_id' => $user->outlet_id ?? null,
            'outlet' => $user->outlet ? [
                'id' => $user->outlet->id,
                'outlet_name' => $user->outlet->outlet_name,
                'outlet_code' => $user->outlet->outlet_code,
            ] : null,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
