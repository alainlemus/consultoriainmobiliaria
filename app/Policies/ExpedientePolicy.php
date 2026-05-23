<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Expediente;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpedientePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Expediente');
    }

    public function view(AuthUser $authUser, Expediente $expediente): bool
    {
        return $authUser->can('View:Expediente');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Expediente');
    }

    public function update(AuthUser $authUser, Expediente $expediente): bool
    {
        return $authUser->can('Update:Expediente');
    }

    public function delete(AuthUser $authUser, Expediente $expediente): bool
    {
        return $authUser->can('Delete:Expediente');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Expediente');
    }

    public function restore(AuthUser $authUser, Expediente $expediente): bool
    {
        return $authUser->can('Restore:Expediente');
    }

    public function forceDelete(AuthUser $authUser, Expediente $expediente): bool
    {
        return $authUser->can('ForceDelete:Expediente');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Expediente');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Expediente');
    }

    public function replicate(AuthUser $authUser, Expediente $expediente): bool
    {
        return $authUser->can('Replicate:Expediente');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Expediente');
    }

}