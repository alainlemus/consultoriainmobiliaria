<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Testimonio;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestimonioPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Testimonio');
    }

    public function view(AuthUser $authUser, Testimonio $testimonio): bool
    {
        return $authUser->can('View:Testimonio');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Testimonio');
    }

    public function update(AuthUser $authUser, Testimonio $testimonio): bool
    {
        return $authUser->can('Update:Testimonio');
    }

    public function delete(AuthUser $authUser, Testimonio $testimonio): bool
    {
        return $authUser->can('Delete:Testimonio');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Testimonio');
    }

    public function restore(AuthUser $authUser, Testimonio $testimonio): bool
    {
        return $authUser->can('Restore:Testimonio');
    }

    public function forceDelete(AuthUser $authUser, Testimonio $testimonio): bool
    {
        return $authUser->can('ForceDelete:Testimonio');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Testimonio');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Testimonio');
    }

    public function replicate(AuthUser $authUser, Testimonio $testimonio): bool
    {
        return $authUser->can('Replicate:Testimonio');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Testimonio');
    }

}