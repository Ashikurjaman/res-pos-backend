<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $guard = 'web';

        // ---------- 1. All permissions ----------
        $allPermissions = [
            'dashboard.view', 'dashboard.manage',
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage',
            'outlets.view', 'outlets.create', 'outlets.edit', 'outlets.delete', 'outlets.manage',
            'products.view', 'products.create', 'products.edit', 'products.delete', 'products.manage',
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete', 'orders.manage', 'orders.process',
            'reports.view', 'reports.export', 'reports.manage',
            'settings.view', 'settings.edit', 'settings.manage',
        ];

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        // ---------- 2. Role => permissions map ----------
        $roleMap = [
            'superadmin' => $allPermissions,
            'admin' => [
                'dashboard.view', 'dashboard.manage',
                'users.view', 'users.create', 'users.edit',
                'outlets.view', 'outlets.create', 'outlets.edit',
                'products.view', 'products.create', 'products.edit',
                'orders.view', 'orders.create', 'orders.edit', 'orders.process',
                'reports.view', 'reports.export',
            ],
            'author' => [
                'dashboard.view',
                'products.view', 'products.create', 'products.edit',
                'orders.view', 'orders.create',
                'reports.view',
            ],
            'store' => [
                'dashboard.view',
                'products.view',
                'orders.view', 'orders.create', 'orders.edit', 'orders.process',
            ],
            'kitchen' => [
                'dashboard.view',
                'orders.view', 'orders.process',
            ],
            'user' => [],
        ];

        // ---------- 3. Create roles with permissions ----------
        foreach ($roleMap as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $role->syncPermissions($perms);
        }

        // ---------- 4. Migrate existing users' old role (only if column still exists) ----------
        if (Schema::hasColumn('users', 'role')) {
            $users = DB::table('users')->select('id', 'role')->get();
            foreach ($users as $u) {
                if (!$u->role) continue;
                $user = User::find($u->id);
                if ($user && !$user->hasRole($u->role)) {
                    $user->assignRole($u->role);
                }
            }
        }
        // Fresh install e 'role' column nai, tai eituku skip hobe — normal behavior.
    }

    public function down()
    {
        // No rollback needed for data migration
    }
};
