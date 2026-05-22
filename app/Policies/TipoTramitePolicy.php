<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TipoTramite;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoTramitePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TipoTramite');
    }

    public function view(AuthUser $authUser, TipoTramite $tipoTramite): bool
    {
        return $authUser->can('View:TipoTramite');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TipoTramite');
    }

    public function update(AuthUser $authUser, TipoTramite $tipoTramite): bool
    {
        return $authUser->can('Update:TipoTramite');
    }

    public function delete(AuthUser $authUser, TipoTramite $tipoTramite): bool
    {
        return $authUser->can('Delete:TipoTramite');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TipoTramite');
    }

    public function restore(AuthUser $authUser, TipoTramite $tipoTramite): bool
    {
        return $authUser->can('Restore:TipoTramite');
    }

    public function forceDelete(AuthUser $authUser, TipoTramite $tipoTramite): bool
    {
        return $authUser->can('ForceDelete:TipoTramite');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TipoTramite');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TipoTramite');
    }

    public function replicate(AuthUser $authUser, TipoTramite $tipoTramite): bool
    {
        return $authUser->can('Replicate:TipoTramite');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TipoTramite');
    }

}