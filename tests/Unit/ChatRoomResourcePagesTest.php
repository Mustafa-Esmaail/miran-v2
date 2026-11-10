<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatRoomResourcePagesTest extends TestCase
{
    #[Test]
    public function it_uses_single_index_page_for_chat_rooms(): void
    {
        $pages = ChatRoomResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayNotHasKey('view', $pages);
    }
}
