<?php

namespace RushHour\Test\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RushHour\Web\DrawEndpoint;
use RushHour\Web\EndpointFactory;
use RushHour\Web\SolveEndpoint;

class EndpointFactoryTest extends TestCase
{
    /**
     * @param string $endpointString
     * @param class-string $endpointClass
     */
    #[DataProvider('endpointProvider')]
    public function testGetEndpoint(string $endpointString, string $endpointClass): void
    {
        $factory = new EndpointFactory();

        $endpoint = $factory->getEndpoint(['action' => $endpointString]);

        $this->assertInstanceOf($endpointClass, $endpoint);
    }

    /**
     * @return list<list{string,class-string}>
     */
    public static function endpointProvider(): array
    {
        return [
            ['solve',SolveEndpoint::class],
            ['draw',DrawEndpoint::class],
        ];
    }
}
