<?php

namespace Bleez\Pagseguro\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;


class InstallData implements InstallDataInterface
{

    private $_scopeConfig;

    protected $attributeModel;


    public function __construct(\Magento\Config\Model\ResourceModel\Config $scopeConfig, \Magento\Customer\Model\Attribute $attributeModel)
    {
        $this->_scopeConfig = $scopeConfig;
        $this->attributeModel = $attributeModel;
    }


    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->_scopeConfig->saveConfig(
            'customer/create_account/vat_frontend_visibility',
            1,
            'default',
            0
        );

        $this->_scopeConfig->saveConfig(
            'customer/address/street_lines',
            4,
            'default',
            0
        );

        $this->_scopeConfig->saveConfig(
            'customer/address/taxvat_show',
            'req',
            'default',
            0
        );

        $this->attributeModel->loadByCode(2, 'street');
        $this->attributeModel->setData('multiline_count', 4);
        $this->attributeModel->save();
    }
}