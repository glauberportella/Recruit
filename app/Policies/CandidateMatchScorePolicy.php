<?php

namespace App\Policies;

use App\Models\CandidateMatchScore;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidateMatchScorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_job::candidates');
    }

    public function view(User $user, CandidateMatchScore $candidateMatchScore): bool
    {
        return $user->can('view_job::candidates');
    }

    public function create(User $user): bool
    {
        return $user->can('create_job::candidates');
    }

    public function delete(User $user, CandidateMatchScore $candidateMatchScore): bool
    {
        return $user->can('delete_job::candidates');
    }
}
