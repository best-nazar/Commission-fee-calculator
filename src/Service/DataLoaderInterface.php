<?php

namespace App\Service;

use App\Entity\UserOperation;

interface DataLoaderInterface
{
    /** 
     * @return array<UserOperation> 
     **/
    public function load(): array;

    /**
     * Path to data source to load from
     */
    public function setSourcePath(string $filePath): void;
}
