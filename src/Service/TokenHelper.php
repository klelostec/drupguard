<?php

namespace App\Service;

class TokenHelper
{

    public function generateToken()
    {
        return bin2hex(random_bytes(60));
    }
}