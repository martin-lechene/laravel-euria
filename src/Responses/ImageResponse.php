<?php

namespace MartinLechene\Euria\Responses;

class ImageResponse
{
    public readonly array $images;

    public function __construct(array $data)
    {
        $this->images = array_map(
            fn ($item) => $item['url'] ?? $item['b64_json'] ?? null,
            $data['data'] ?? []
        );
    }

    public function first(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function all(): array
    {
        return $this->images;
    }
}
