<?php

namespace RushHour\Test\Web;

use RushHour\Web\BoardEndpoint;
use RushHour\Web\DrawEndpoint;

class DrawEndpointTest extends BoardEndpointTest
{
    protected function getEndpoint(): DrawEndpoint
    {
        return new DrawEndpoint();
    }

    public function testExecute(): void
    {
        $endpoint = $this->getEndpoint();
        /**
         * This board:
         * @@@@@
         * ..rr@
         * @@@@@
         */
        $endpoint->setParameters(['board' => '3,1$0,1;r2,1R2']);
        $drawing = $endpoint->execute();

        $this->assertSame(
            [
                '@@@@@',
                '..rr@',
                '@@@@@',
            ],
            $drawing
        );
    }
}
