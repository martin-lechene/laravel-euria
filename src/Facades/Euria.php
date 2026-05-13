<?php

namespace MartinLechene\Euria\Facades;

use Generator;
use Illuminate\Support\Facades\Facade;
use MartinLechene\Euria\EuriaManager;
use MartinLechene\Euria\Responses\AudioResponse;
use MartinLechene\Euria\Responses\EmbeddingResponse;
use MartinLechene\Euria\Responses\ImageResponse;
use MartinLechene\Euria\Responses\TextResponse;
use MartinLechene\Euria\Testing\EuriaFake;

/**
 * @method static TextResponse text(string $prompt, array $options = [])
 * @method static Generator stream(string $prompt, array $options = [])
 * @method static EmbeddingResponse embed(string|array $input, array $options = [])
 * @method static ImageResponse image(string $prompt, array $options = [])
 * @method static AudioResponse transcribe(string $audioPath, array $options = [])
 * @method static static withToken(string $token)
 * @method static static model(string $model)
 * @method static static timeout(int $seconds)
 *
 * @see EuriaManager
 */
class Euria extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EuriaManager::class;
    }

    public static function fake(): EuriaFake
    {
        $fake = new EuriaFake(app());
        app()->instance(EuriaManager::class, $fake);

        return $fake;
    }
}
