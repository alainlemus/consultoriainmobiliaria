<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Comision;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComisionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Comision');
    }

    public function view(AuthUser $authUser, Comision $comision): bool
    {
        return $authUser->can('View:Comision');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Comision');
    }

    public function update(AuthUser $authUser, Comision $comision): bool
    {
        return $authUser->can('Update:Comision');
    }

    public function delete(AuthUser $authUser, Comision $comision): bool
    {
        return $authUser->can('Delete:Comision');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Comision');
    }

    public function restore(AuthUser $authUser, Comision $comision): bool
    {
        return $authUser->can('Restore:Comision');
    }

    public function forceDelete(AuthUser $authUser, Comision $comision): bool
    {
        return $authUser->can('ForceDelete:Comision');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Comision');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Comision');
    }

    public function replicate(AuthUser $authUser, Comision $comision): bool
    {
        return $authUser->can('Replicate:Comision');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Comision');
    }

}