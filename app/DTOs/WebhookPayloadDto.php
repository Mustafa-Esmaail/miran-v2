<?php

namespace App\DTOs;

class WebhookPayloadDto
{
    public function __construct(
        public string $provider,
        public string $reference,
        public string $status,
        public array $payload,
    ) {}
}
