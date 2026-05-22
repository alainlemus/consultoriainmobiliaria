<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cobertura;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoberturaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Cobertura');
    }

    public function view(AuthUser $authUser, Cobertura $cobertura): bool
    {
        return $authUser->can('View:Cobertura');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Cobertura');
    }

    public function update(AuthUser $authUser, Cobertura $cobertura): bool
    {
        return $authUser->can('Update:Cobertura');
    }

    public function delete(AuthUser $authUser, Cobertura $cobertura): bool
    {
        return $authUser->can('Delete:Cobertura');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Cobertura');
    }

    public function restore(AuthUser $authUser, Cobertura $cobertura): bool
    {
        return $authUser->can('Restore:Cobertura');
    }

    public function forceDelete(AuthUser $authUser, Cobertura $cobertura): bool
    {
        return $authUser->can('ForceDelete:Cobertura');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Cobertura');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Cobertura');
    }

    public function replicate(AuthUser $authUser, Cobertura $cobertura): bool
    {
        return $authUser->can('Replicate:Cobertura');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Cobertura');
    }

}