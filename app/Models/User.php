<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Maravel\Models\AuthenticatableBase;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends AuthenticatableBase
{
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'activated',
        'password_change_required',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les accesseurs ajoutés à la sérialisation.
     *
     * `ability_rules` (format CASL) est calculé dynamiquement depuis les rôles
     * assignés à l'utilisateur (trait HasRoles), pour être consommé par CASL
     * côté frontend.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'ability_rules',
    ];

    /**
     * Casts d'énumération (libellés lisibles)
     *
     * @var array
     */
    protected $enumCasts = [
        [
            'colum_name' => 'activated',
            'additional_column_name' => 'activated_fr',
            'choices' => [
                1 => 'Oui',
                0 => 'Non',
            ],
        ],
        [
            'colum_name' => 'password_change_required',
            'additional_column_name' => 'password_change_required_fr',
            'choices' => [
                1 => 'Obligatoire',
                0 => 'Facultatif',
            ],
        ],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activated' => 'boolean',
            'password_change_required' => 'boolean',
        ];
    }

    /**
     * Règles CASL calculées depuis les rôles (HasRoles), complétées par une règle
     * de base accordée à tout utilisateur authentifié pour gérer son propre compte
     * (ex: changer son mot de passe), indépendamment de ses permissions RBAC.
     */
    public function getAbilityRulesAttribute(): array
    {
        $rules = parent::getAbilityRulesAttribute();

        $rules[] = ['subject' => ['Auth'], 'action' => ['read']];

        return $rules;
    }
}
