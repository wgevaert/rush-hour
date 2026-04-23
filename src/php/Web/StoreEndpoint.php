<?php

namespace RushHour\Web;

use RushHour\Storage\Storage;

class StoreEndpoint extends BoardEndpoint
{
    private Storage $storage;

    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function execute(): array
    {
        return [
            'success' => true,
            'id' => $this->storage->storeBoard($this->board),
        ];
    }
}
