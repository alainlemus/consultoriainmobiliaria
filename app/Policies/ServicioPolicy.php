<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Servicio;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicioPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Servicio');
    }

    public function view(AuthUser $authUser, Servicio $servicio): bool
    {
        return $authUser->can('View:Servicio');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Servicio');
    }

    public function update(AuthUser $authUser, Servicio $servicio): bool
    {
        return $authUser->can('Update:Servicio');
    }

    public function delete(AuthUser $authUser, Servicio $servicio): bool
    {
        return $authUser->can('Delete:Servicio');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Servicio');
    }

    public function restore(AuthUser $authUser, Servicio $servicio): bool
    {
        return $authUser->can('Restore:Servicio');
    }

    public function forceDelete(AuthUser $authUser, Servicio $servicio): bool
    {
        return $authUser->can('ForceDelete:Servicio');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Servicio');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Servicio');
    }

    public function replicate(AuthUser $authUser, Servicio $servicio): bool
    {
        return $authUser->can('Replicate:Servicio');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Servicio');
    }

}