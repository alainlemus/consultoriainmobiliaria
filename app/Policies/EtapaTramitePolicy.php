<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EtapaTramite;
use Illuminate\Auth\Access\HandlesAuthorization;

class EtapaTramitePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EtapaTramite');
    }

    public function view(AuthUser $authUser, EtapaTramite $etapaTramite): bool
    {
        return $authUser->can('View:EtapaTramite');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EtapaTramite');
    }

    public function update(AuthUser $authUser, EtapaTramite $etapaTramite): bool
    {
        return $authUser->can('Update:EtapaTramite');
    }

    public function delete(AuthUser $authUser, EtapaTramite $etapaTramite): bool
    {
        return $authUser->can('Delete:EtapaTramite');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EtapaTramite');
    }

    public function restore(AuthUser $authUser, EtapaTramite $etapaTramite): bool
    {
        return $authUser->can('Restore:EtapaTramite');
    }

    public function forceDelete(AuthUser $authUser, EtapaTramite $etapaTramite): bool
    {
        return $authUser->can('ForceDelete:EtapaTramite');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EtapaTramite');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EtapaTramite');
    }

    public function replicate(AuthUser $authUser, EtapaTramite $etapaTramite): bool
    {
        return $authUser->can('Replicate:EtapaTramite');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EtapaTramite');
    }

}