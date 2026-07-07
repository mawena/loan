<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeder initial du système RBAC.
 *
 * Crée :
 * - un rôle "admin" super-administrateur (tous les droits),
 * - un jeu de permissions de base (utilisateurs, rôles, permissions).
 *
 * Adaptez librement à votre domaine. Lancez-le via :
 *   php artisan db:seed --class=RolePermissionSeeder
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Rôle super-administrateur : accorde automatiquement « manage / all ».
        Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'label' => 'Administrateur',
                'description' => 'Accès complet à toutes les ressources',
                'is_super_admin' => true,
            ]
        );

        // Rôle client
        $clientRole = Role::firstOrCreate(
            ['name' => 'client'],
            [
                'label' => 'Client',
                'description' => 'Peut gérer ses simulations',
                'is_super_admin' => false,
            ]
        );

        // Permissions de base. Ajoutez-en autant que nécessaire à l'avenir.
        $permissions = [
            ['action' => 'read', 'subject' => 'user', 'label' => 'Voir les utilisateurs'],
            ['action' => 'create', 'subject' => 'user', 'label' => 'Créer un utilisateur'],
            ['action' => 'update', 'subject' => 'user', 'label' => 'Modifier un utilisateur'],
            ['action' => 'delete', 'subject' => 'user', 'label' => 'Supprimer un utilisateur'],
            ['action' => 'manage', 'subject' => 'role', 'label' => 'Gérer les rôles'],
            ['action' => 'manage', 'subject' => 'permission', 'label' => 'Gérer les permissions'],
            ['action' => 'read', 'subject' => 'simulation', 'label' => 'Voir ses simulations'],
            ['action' => 'create', 'subject' => 'simulation', 'label' => 'Créer une simulation'],
            ['action' => 'update', 'subject' => 'simulation', 'label' => 'Modifier ses simulations'],
            ['action' => 'delete', 'subject' => 'simulation', 'label' => 'Supprimer ses simulations'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['action' => $permission['action'], 'subject' => $permission['subject']],
                ['label' => $permission['label'] ?? null]
            );
        }

        // Assigner les permissions de simulation au client
        $clientPerms = Permission::where('subject', 'simulation')->get();
        if ($clientPerms->isNotEmpty()) {
            $clientRole->permissions()->syncWithoutDetaching($clientPerms->pluck('id'));
        }
    }
}
