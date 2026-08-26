<?php

declare(strict_types=1);

namespace App\Service\Bot;

use App\Game\Mode\GameModeEnum;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-type BotResponse array{
 *     cards?: array<string>,
 *     data?: array<string, mixed>,
 * }
 */
class BotClient
{
    private HttpClientInterface $client;

    public function __construct(
        string $botServer,
    ) {
        $this->client = HttpClient::createForBaseUri($botServer);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return BotResponse
     */
    public function play(GameModeEnum $gameMode, array $body = []): array
    {
        // one route per game mode - same container today, trivially splittable
        // into one service per game if a strategy ever needs its own runtime
        $response = $this->client->request('POST', '/move/'.$gameMode->value, ['json' => $body]);

        return $response->toArray();
    }
}
