<?php

declare(strict_types=1);

namespace App\Service\Bot;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-type BotResponse array{
 *		cards?: array<array{
 *		   suit: string,
 *		   rank: string,
 *	    }>,
 *	    data?: array<string, mixed>,
 *	}
 */
final class BotClient
{
    private HttpClientInterface $client;

    public function __construct(
        string $botServer,
    ) {
        $this->client = HttpClient::createForBaseUri($botServer);
    }

    /**
     * @param array<mixed> $body
     *
     * @return BotResponse
     */
    public function play(array $body = []): array
    {
        $response = $this->client->request('POST', '/move', ['json' => $body]);

        return $response->toArray();
    }
}
