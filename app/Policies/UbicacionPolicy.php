<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Ubicacion;
use Illuminate\Auth\Access\HandlesAuthorization;

class UbicacionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Ubicacion');
    }

    public function view(AuthUser $authUser, Ubicacion $ubicacion): bool
    {
        return $authUser->can('View:Ubicacion');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Ubicacion');
    }

    public function update(AuthUser $authUser, Ubicacion $ubicacion): bool
    {
        return $authUser->can('Update:Ubicacion');
    }

    public function delete(AuthUser $authUser, Ubicacion $ubicacion): bool
    {
        return $authUser->can('Delete:Ubicacion');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Ubicacion');
    }

    public function restore(AuthUser $authUser, Ubicacion $ubicacion): bool
    {
        return $authUser->can('Restore:Ubicacion');
    }

    public function forceDelete(AuthUser $authUser, Ubicacion $ubicacion): bool
    {
        return $authUser->can('ForceDelete:Ubicacion');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ubicacion');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ubicacion');
    }

    public function replicate(AuthUser $authUser, Ubicacion $ubicacion): bool
    {
        return $authUser->can('Replicate:Ubicacion');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ubicacion');
    }

}