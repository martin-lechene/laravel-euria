<?php

namespace MartinLechene\Euria\Concerns;

use Illuminate\Foundation\Auth\User;

trait RemembersConversations
{
    protected ?string $conversationId = null;

    protected ?User $forUser = null;

    public function forUser(User $user): static
    {
        $this->forUser = $user;

        return $this;
    }

    public function continue(string $conversationId, ?User $as = null): static
    {
        $this->conversationId = $conversationId;
        if ($as) {
            $this->forUser = $as;
        }

        return $this;
    }

    public function getConversationId(): ?string
    {
        return $this->conversationId;
    }

    public function getUser(): ?User
    {
        return $this->forUser;
    }
}
