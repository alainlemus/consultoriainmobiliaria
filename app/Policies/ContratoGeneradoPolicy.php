<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ContratoGenerado;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContratoGeneradoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContratoGenerado');
    }

    public function view(AuthUser $authUser, ContratoGenerado $contratoGenerado): bool
    {
        return $authUser->can('View:ContratoGenerado');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContratoGenerado');
    }

    public function update(AuthUser $authUser, ContratoGenerado $contratoGenerado): bool
    {
        return $authUser->can('Update:ContratoGenerado');
    }

    public function delete(AuthUser $authUser, ContratoGenerado $contratoGenerado): bool
    {
        return $authUser->can('Delete:ContratoGenerado');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ContratoGenerado');
    }

    public function restore(AuthUser $authUser, ContratoGenerado $contratoGenerado): bool
    {
        return $authUser->can('Restore:ContratoGenerado');
    }

    public function forceDelete(AuthUser $authUser, ContratoGenerado $contratoGenerado): bool
    {
        return $authUser->can('ForceDelete:ContratoGenerado');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContratoGenerado');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContratoGenerado');
    }

    public function replicate(AuthUser $authUser, ContratoGenerado $contratoGenerado): bool
    {
        return $authUser->can('Replicate:ContratoGenerado');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContratoGenerado');
    }

}