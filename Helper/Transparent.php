<?php

namespace Bleez\Pagseguro\Helper;

class Transparent extends \Bleez\Pagseguro\Helper\AbstractHelper
{
    public function getSession($credentials){
        return \PagSeguroSessionService::getSession($credentials);
    }
}