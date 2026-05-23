<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FotoCliente;
use Illuminate\Auth\Access\HandlesAuthorization;

class FotoClientePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FotoCliente');
    }

    public function view(AuthUser $authUser, FotoCliente $fotoCliente): bool
    {
        return $authUser->can('View:FotoCliente');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FotoCliente');
    }

    public function update(AuthUser $authUser, FotoCliente $fotoCliente): bool
    {
        return $authUser->can('Update:FotoCliente');
    }

    public function delete(AuthUser $authUser, FotoCliente $fotoCliente): bool
    {
        return $authUser->can('Delete:FotoCliente');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FotoCliente');
    }

    public function restore(AuthUser $authUser, FotoCliente $fotoCliente): bool
    {
        return $authUser->can('Restore:FotoCliente');
    }

    public function forceDelete(AuthUser $authUser, FotoCliente $fotoCliente): bool
    {
        return $authUser->can('ForceDelete:FotoCliente');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FotoCliente');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FotoCliente');
    }

    public function replicate(AuthUser $authUser, FotoCliente $fotoCliente): bool
    {
        return $authUser->can('Replicate:FotoCliente');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FotoCliente');
    }

}