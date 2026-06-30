<?php

namespace App\Policies;

use App\Models\User;
use Awcodes\Curator\Models\Media;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Media $media): bool
    {
        return true;
    }

    public function delete(User $user, Media $media): bool
    {
        return true;
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    public function download(User $user): bool
    {
        return true;
    }
}
