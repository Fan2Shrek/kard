<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class StripeClient
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private string $apiKey,
        string $baseUrl,
    ) {
        $this->httpClient = HttpClient::createForBaseUri($baseUrl);
    }

    public function pay(float $amount): string
    {
        $response = $this->httpClient->request('POST', '/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Stripe-Version' => '2025-01-27.acacia',
            ],
            'body' => [
                'amount' => $amount,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
            ],
        ]);

        return $response->toArray()['id'];
    }
}
