<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use App\Services\SiteSettings;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasVerifiedEmail();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Article $article): bool
    {
        return $this->viewAny($user) && ($user->isSuperAdmin() || $article->user_id === $user->id);
    }

    public function update(User $user, Article $article): bool
    {
        return $this->view($user, $article) && ! $article->trashed_at;
    }

    public function review(User $user, Article $article): bool
    {
        return $user->isSuperAdmin();
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->isSuperAdmin();
    }

    public function trash(User $user, Article $article): bool
    {
        return $this->view($user, $article) && ! $article->trashed_at && ($user->isSuperAdmin() || ! $article->published_revision_id);
    }

    public function restore(User $user, Article $article): bool
    {
        return $this->view($user, $article) && (bool) $article->trashed_at;
    }

    public function curate(User $user, Article $article): bool
    {
        return $this->update($user, $article) && ($user->isSuperAdmin() || app(SiteSettings::class)->snapshot('general')['value']['author_ai_enabled']);
    }
}
