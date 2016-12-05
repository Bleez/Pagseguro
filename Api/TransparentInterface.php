<?php

namespace Bleez\Pagseguro\Api;

interface TransparentInterface
{
    /**
     * @return string;
     */
    public function getSessionId();
}
