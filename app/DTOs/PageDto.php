<?php

namespace App\DTOs;

class PageDto
{
    public function __construct(
        public string $title,
        public string $slug,
        public ?string $body,
        public ?string $template,
    ) {}
}
