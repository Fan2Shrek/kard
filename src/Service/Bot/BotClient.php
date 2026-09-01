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
     * @param object|array<string, mixed> $body json_encode'd as-is - a DTO's public
     *                                          properties, backed enums as their value
     *
     * @return BotResponse
     */
    public function play(GameModeEnum $gameMode, object|array $body = []): array
    {
        // one route per game mode - same container today, trivially splittable
        // into one service per game if a strategy ever needs its own runtime
        $response = $this->client->request('POST', '/move/'.$gameMode->value, ['json' => $body]);

        return $response->toArray();
    }
}
