<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Maravel\Policies\BasePolicy;

class SimulationPolicy extends BasePolicy
{
    protected $modelName = "simulation";

    public function viewAny($connectedUser)
    {
        return parent::viewAny($connectedUser);
    }

    public function view($connectedUser, Model $simulation)
    {
        if ($connectedUser->id === $simulation->user_id) {
            return Response::allow();
        }
        return parent::view($connectedUser, $simulation);
    }

    public function create($connectedUser)
    {
        return parent::create($connectedUser);
    }

    public function update($connectedUser, Model $simulation)
    {
        if ($connectedUser->id === $simulation->user_id) {
            return Response::allow();
        }
        return parent::update($connectedUser, $simulation);
    }

    public function delete($connectedUser, Model $simulation)
    {
        if ($connectedUser->id === $simulation->user_id) {
            return Response::allow();
        }
        return parent::delete($connectedUser, $simulation);
    }
}
