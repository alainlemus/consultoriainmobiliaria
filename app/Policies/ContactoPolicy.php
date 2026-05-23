<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Contacto;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Contacto');
    }

    public function view(AuthUser $authUser, Contacto $contacto): bool
    {
        return $authUser->can('View:Contacto');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Contacto');
    }

    public function update(AuthUser $authUser, Contacto $contacto): bool
    {
        return $authUser->can('Update:Contacto');
    }

    public function delete(AuthUser $authUser, Contacto $contacto): bool
    {
        return $authUser->can('Delete:Contacto');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Contacto');
    }

    public function restore(AuthUser $authUser, Contacto $contacto): bool
    {
        return $authUser->can('Restore:Contacto');
    }

    public function forceDelete(AuthUser $authUser, Contacto $contacto): bool
    {
        return $authUser->can('ForceDelete:Contacto');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Contacto');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Contacto');
    }

    public function replicate(AuthUser $authUser, Contacto $contacto): bool
    {
        return $authUser->can('Replicate:Contacto');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Contacto');
    }

}