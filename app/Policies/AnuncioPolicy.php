<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Anuncio;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnuncioPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Anuncio');
    }

    public function view(AuthUser $authUser, Anuncio $anuncio): bool
    {
        return $authUser->can('View:Anuncio');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Anuncio');
    }

    public function update(AuthUser $authUser, Anuncio $anuncio): bool
    {
        return $authUser->can('Update:Anuncio');
    }

    public function delete(AuthUser $authUser, Anuncio $anuncio): bool
    {
        return $authUser->can('Delete:Anuncio');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Anuncio');
    }

    public function restore(AuthUser $authUser, Anuncio $anuncio): bool
    {
        return $authUser->can('Restore:Anuncio');
    }

    public function forceDelete(AuthUser $authUser, Anuncio $anuncio): bool
    {
        return $authUser->can('ForceDelete:Anuncio');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Anuncio');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Anuncio');
    }

    public function replicate(AuthUser $authUser, Anuncio $anuncio): bool
    {
        return $authUser->can('Replicate:Anuncio');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Anuncio');
    }

}