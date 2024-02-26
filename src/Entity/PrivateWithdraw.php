<?php

namespace App\Entity;

final class PrivateWithdraw 
{
    public float $amount = 0;

    public int $count = 0;

    public bool $limitIsReached = false;
}