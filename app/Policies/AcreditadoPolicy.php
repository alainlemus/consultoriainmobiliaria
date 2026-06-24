<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcreditadoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Acreditado');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Acreditado');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Acreditado');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Acreditado');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Acreditado');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Acreditado');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Acreditado');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Acreditado');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Acreditado');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Acreditado');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Acreditado');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Acreditado');
    }

}