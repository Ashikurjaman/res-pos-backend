<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');

            $query = Role::query();

            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            // Get the roles first
            $roles = $query->paginate($perPage);

            // Manually add user count for each role
            if ($roles->isNotEmpty()) {
                $roleIds = $roles->pluck('id')->toArray();

                $userCounts = DB::table('model_has_roles')
                    ->whereIn('role_id', $roleIds)
                    ->where('model_type', 'App\\Models\\User')
                    ->select('role_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('role_id')
                    ->pluck('count', 'role_id')
                    ->toArray();

                $roles->each(function ($role) use ($userCounts) {
                    $role->users_count = $userCounts[$role->id] ?? 0;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all permissions grouped by category.
     */
    public function permissions()
    {
        try {
            $permissions = Permission::all();

            $grouped = [];
            foreach ($permissions as $permission) {
                $parts = explode('.', $permission->name);
                $group = $parts[0] ?? 'general';

                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }

                $grouped[$group][] = [
                    'id' => $permission->id,
                    'name' => $permission->name
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $grouped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'sometimes|array',
                'permissions.*' => 'string'
            ]);

            $role = Role::create(['name' => $validated['name']]);

            if (!empty($validated['permissions'])) {
                $permissions = Permission::whereIn('name', $validated['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show($id)
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            if ($role->name === 'superadmin' && $request->has('name') && $request->name !== 'superadmin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change superadmin role name'
                ], 403);
            }

            if ($request->has('name')) {
                $request->validate([
                    'name' => 'required|string|max:255|unique:roles,name,' . $id
                ]);
                $role->name = $request->name;
                $role->save();
            }

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('name', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);

            if ($role->name === 'superadmin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete superadmin role'
                ], 403);
            }

            $userCount = DB::table('model_has_roles')
                ->where('role_id', $id)
                ->where('model_type', 'App\\Models\\User')
                ->count();

            if ($userCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete role with {$userCount} assigned user(s)"
                ], 403);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
