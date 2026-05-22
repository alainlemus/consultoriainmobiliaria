<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentoRequerido;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentoRequeridoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentoRequerido');
    }

    public function view(AuthUser $authUser, DocumentoRequerido $documentoRequerido): bool
    {
        return $authUser->can('View:DocumentoRequerido');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentoRequerido');
    }

    public function update(AuthUser $authUser, DocumentoRequerido $documentoRequerido): bool
    {
        return $authUser->can('Update:DocumentoRequerido');
    }

    public function delete(AuthUser $authUser, DocumentoRequerido $documentoRequerido): bool
    {
        return $authUser->can('Delete:DocumentoRequerido');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentoRequerido');
    }

    public function restore(AuthUser $authUser, DocumentoRequerido $documentoRequerido): bool
    {
        return $authUser->can('Restore:DocumentoRequerido');
    }

    public function forceDelete(AuthUser $authUser, DocumentoRequerido $documentoRequerido): bool
    {
        return $authUser->can('ForceDelete:DocumentoRequerido');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentoRequerido');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentoRequerido');
    }

    public function replicate(AuthUser $authUser, DocumentoRequerido $documentoRequerido): bool
    {
        return $authUser->can('Replicate:DocumentoRequerido');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentoRequerido');
    }

}