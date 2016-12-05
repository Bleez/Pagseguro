<?php
namespace Bleez\Pagseguro\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

final class ConfigProvider extends \Magento\Payment\Model\CcGenericConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pagseguro_cc';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * ConfigProvider constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\UrlInterface $urlBuilder) {
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->scopeConfig->getValue('payment/pagseguro_cc/active'),
                    'availableCardTypes' => explode(',', $this->scopeConfig->getValue('payment/pagseguro_cc/cctypes')),
                ]
            ]
        ];
    }
}
