<?php

namespace RushHour\Test\Web;

use RushHour\Web\SolveEndpoint;

class SolveEndpointTest extends BoardEndpointTest
{
    protected function getEndpoint(): SolveEndpoint
    {
        return new SolveEndpoint();
    }

    public function testExecute(): void
    {
        $endpoint = $this->getEndpoint();
        /**
         * This board:
         * @@@@@
         * ..rr@
         * @@@@@
         * Solution: Move r west twice
         */
        $endpoint->setParameters(['board' => '3,1$0,1;r2,1R2']);

        $result = $endpoint->execute();
        $this->assertArrayHasKey('moves', $result);
        $this->assertCount(1, $result['moves']);
        $this->assertEquals('r2W', $result['moves'][0]);
    }
}
