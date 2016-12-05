<?php

namespace Bleez\Pagseguro\Model\System\Config;

class Environment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'sandbox', 'label' => 'Sandbox'),
            array('value' => 'production', 'label' => 'Produção'),
        );
    }
}