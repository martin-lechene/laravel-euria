<?php

namespace MartinLechene\Euria\Testing;

use MartinLechene\Euria\EuriaManager;
use MartinLechene\Euria\Responses\AudioResponse;
use MartinLechene\Euria\Responses\EmbeddingResponse;
use MartinLechene\Euria\Responses\ImageResponse;
use MartinLechene\Euria\Responses\TextResponse;
use PHPUnit\Framework\Assert;

class EuriaFake extends EuriaManager
{
    protected array $textResponses = [];

    protected array $imageResponses = [];

    protected array $embeddingResponses = [];

    protected array $audioResponses = [];

    protected array $recordedCalls = [];

    public function fakeText(string $text, array $extra = []): static
    {
        $this->textResponses[] = new TextResponse(array_merge([
            'model' => 'fake-model',
            'choices' => [['message' => ['content' => $text], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ], $extra));

        return $this;
    }

    public function fakeImage(string $url): static
    {
        $this->imageResponses[] = new ImageResponse(['data' => [['url' => $url]]]);

        return $this;
    }

    public function fakeEmbedding(array $vector): static
    {
        $this->embeddingResponses[] = new EmbeddingResponse(['data' => [['embedding' => $vector]], 'usage' => []]);

        return $this;
    }

    public function fakeAudio(string $transcription): static
    {
        $this->audioResponses[] = new AudioResponse(['text' => $transcription]);

        return $this;
    }

    public function text(string $prompt, array $options = []): TextResponse
    {
        $this->recordedCalls[] = ['method' => 'text', 'prompt' => $prompt, 'options' => $options];

        return array_shift($this->textResponses) ?? new TextResponse([
            'model' => 'fake-model',
            'choices' => [['message' => ['content' => 'Fake response'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 10, 'total_tokens' => 15],
        ]);
    }

    public function image(string $prompt, array $options = []): ImageResponse
    {
        $this->recordedCalls[] = ['method' => 'image', 'prompt' => $prompt];

        return array_shift($this->imageResponses) ?? new ImageResponse(['data' => [['url' => 'https://fake.url/image.png']]]);
    }

    public function embed(string|array $input, array $options = []): EmbeddingResponse
    {
        $this->recordedCalls[] = ['method' => 'embed', 'input' => $input];

        return array_shift($this->embeddingResponses) ?? new EmbeddingResponse(['data' => [['embedding' => array_fill(0, 1536, 0.1)]], 'usage' => []]);
    }

    public function transcribe(string $audioPath, array $options = []): AudioResponse
    {
        $this->recordedCalls[] = ['method' => 'transcribe', 'path' => $audioPath];

        return array_shift($this->audioResponses) ?? new AudioResponse(['text' => 'Fake transcription']);
    }

    public function assertTextCalled(int $times = 1): void
    {
        $count = count(array_filter($this->recordedCalls, fn ($c) => $c['method'] === 'text'));
        Assert::assertSame($times, $count, "Expected text() to be called {$times} times, got {$count}.");
    }

    public function assertPromptContains(string $needle): void
    {
        $found = array_filter($this->recordedCalls, fn ($c) => isset($c['prompt']) && str_contains($c['prompt'], $needle));
        Assert::assertNotEmpty($found, "No prompt containing \"{$needle}\" was sent to Euria.");
    }

    public function assertImageCalled(int $times = 1): void
    {
        $count = count(array_filter($this->recordedCalls, fn ($c) => $c['method'] === 'image'));
        Assert::assertSame($times, $count);
    }

    public function assertNothingCalled(): void
    {
        Assert::assertEmpty($this->recordedCalls, 'Expected no calls to Euria, but some were recorded.');
    }

    public function recordedCalls(): array
    {
        return $this->recordedCalls;
    }
}
