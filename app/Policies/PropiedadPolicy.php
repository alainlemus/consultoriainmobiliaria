<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Propiedad;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropiedadPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Propiedad');
    }

    public function view(AuthUser $authUser, Propiedad $propiedad): bool
    {
        return $authUser->can('View:Propiedad');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Propiedad');
    }

    public function update(AuthUser $authUser, Propiedad $propiedad): bool
    {
        return $authUser->can('Update:Propiedad');
    }

    public function delete(AuthUser $authUser, Propiedad $propiedad): bool
    {
        return $authUser->can('Delete:Propiedad');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Propiedad');
    }

    public function restore(AuthUser $authUser, Propiedad $propiedad): bool
    {
        return $authUser->can('Restore:Propiedad');
    }

    public function forceDelete(AuthUser $authUser, Propiedad $propiedad): bool
    {
        return $authUser->can('ForceDelete:Propiedad');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Propiedad');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Propiedad');
    }

    public function replicate(AuthUser $authUser, Propiedad $propiedad): bool
    {
        return $authUser->can('Replicate:Propiedad');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Propiedad');
    }

}