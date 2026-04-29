<?php

namespace MartinLechene\Euria;

use Illuminate\Support\Facades\Facade;
use MartinLechene\Euria\Testing\EuriaFake;

/**
 * @method static \MartinLechene\Euria\Responses\TextResponse text(string $prompt, array $options = [])
 * @method static \Generator stream(string $prompt, array $options = [])
 * @method static \MartinLechene\Euria\Responses\EmbeddingResponse embed(string|array $input, array $options = [])
 * @method static \MartinLechene\Euria\Responses\ImageResponse image(string $prompt, array $options = [])
 * @method static \MartinLechene\Euria\Responses\AudioResponse transcribe(string $audioPath, array $options = [])
 * @method static static withToken(string $token)
 * @method static static model(string $model)
 * @method static static timeout(int $seconds)
 *
 * @see EuriaManager
 */
class EuriaFacade extends Facade
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
