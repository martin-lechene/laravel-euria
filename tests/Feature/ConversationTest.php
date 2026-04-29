<?php

use MartinLechene\Euria\Concerns\Promptable;
use MartinLechene\Euria\Concerns\RemembersConversations;
use MartinLechene\Euria\Contracts\Agent;

class ConversationAgent implements Agent
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        return 'Assistant.';
    }
}

it('peut avoir un forUser', function () {
    $user = Mockery::mock('Illuminate\Foundation\Auth\User');
    $agent = new ConversationAgent;
    $result = $agent->forUser($user);

    expect($result)->toBeInstanceOf(ConversationAgent::class)
        ->and($agent->getUser())->toBe($user);
});

it('peut avoir un conversationId via continue', function () {
    $agent = new ConversationAgent;
    $result = $agent->continue('conv-123');

    expect($result)->toBeInstanceOf(ConversationAgent::class)
        ->and($agent->getConversationId())->toBe('conv-123');
});
