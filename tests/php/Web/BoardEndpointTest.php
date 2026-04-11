<?php

namespace RushHour\Test\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Web\BoardEndpoint;
use UnexpectedValueException;

class BoardEndpointTest extends TestCase
{
    protected function getEndpoint(): BoardEndpoint
    {
        return new class extends BoardEndpoint {
            public function execute(): array
            {
                return [];
            }
        };
    }

    public function testSetParameters(): void
    {
        $endpoint = $this->getEndpoint();

        $endpoint->setParameters(['board' => '1,1$0,1;']);

        $board = $endpoint->getBoard();
        $this->assertInstanceOf(Board::class, $board);
    }

    #[DataProvider('serializerProvider')]
    public function testSetParametersSerializations(string $boardString, string $serialization): void
    {
        $endpoint = $this->getEndpoint();

        $endpoint->setParameters(['board' => $boardString, 'serialization' => $serialization ]);

        $board = $endpoint->getBoard();
        $this->assertInstanceOf(Board::class, $board);
    }

    /**
     * @return list<list{string,string}>
     */
    public static function serializerProvider(): array
    {
        return [
            ['2,2$0,1;r1,1D2','carpos'],
            ["@@@@\n.r.@\n@r.@\n@@@@",'draw'],
        ];
    }

    public function testSetParametersSerializationsThrowsUnknownSerialization(): void
    {
        $endpoint = $this->getEndpoint();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/serialization/i');

        $endpoint->setParameters([ 'board' => '@', 'serialization' => 'nonexistent' ]);
    }

    public function testSetParametersThrowsBoardMissing(): void
    {
        $endpoint = $this->getEndpoint();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/parameter.*board.*required/i');

        $endpoint->setParameters([]);
    }
}
