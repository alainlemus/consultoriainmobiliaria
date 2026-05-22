<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Proceso;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcesoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Proceso');
    }

    public function view(AuthUser $authUser, Proceso $proceso): bool
    {
        return $authUser->can('View:Proceso');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Proceso');
    }

    public function update(AuthUser $authUser, Proceso $proceso): bool
    {
        return $authUser->can('Update:Proceso');
    }

    public function delete(AuthUser $authUser, Proceso $proceso): bool
    {
        return $authUser->can('Delete:Proceso');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Proceso');
    }

    public function restore(AuthUser $authUser, Proceso $proceso): bool
    {
        return $authUser->can('Restore:Proceso');
    }

    public function forceDelete(AuthUser $authUser, Proceso $proceso): bool
    {
        return $authUser->can('ForceDelete:Proceso');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Proceso');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Proceso');
    }

    public function replicate(AuthUser $authUser, Proceso $proceso): bool
    {
        return $authUser->can('Replicate:Proceso');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Proceso');
    }

}