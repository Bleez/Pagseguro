<?php

namespace Bleez\Pagseguro\Block\Adminhtml\System\Config;

class Refresh extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('refresh.phtml');
        }
        return $this;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(
            [
                'html_id' => $element->getHtmlId(),
                'url' => $this->_urlBuilder->getUrl('pagseguro/transaction/refresh'),
            ]
        );

        return $this->_toHtml();
    }
}
