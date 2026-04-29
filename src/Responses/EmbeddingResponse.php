<?php

namespace MartinLechene\Euria\Responses;

class EmbeddingResponse
{
    public readonly array $embeddings;

    public readonly array $usage;

    public function __construct(array $data)
    {
        $this->embeddings = array_map(
            fn ($item) => $item['embedding'],
            $data['data'] ?? []
        );
        $this->usage = $data['usage'] ?? [];
    }

    public function first(): array
    {
        return $this->embeddings[0] ?? [];
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = array_sum(array_map(fn ($x, $y) => $x * $y, $a, $b));
        $normA = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $a)));
        $normB = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $b)));

        return $normA && $normB ? $dot / ($normA * $normB) : 0.0;
    }
}
