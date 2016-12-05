<?php
namespace Bleez\Pagseguro\Plugin\Checkout\Block\Checkout;

use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Event\ManagerInterface as EventManager;


class AttributeMerger
{

    protected $cacheContext;

    protected $eventManager;

    public function __construct(
        CacheContext $cacheContext,
        EventManager $eventManager
    )
    {
        $this->cacheContext = $cacheContext;
        $this->eventManager = $eventManager;

    }

    protected $fieldNames = array('', 'Número', 'Bairro', 'Complemento');

    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
    {
        if(isset($result['street'])){
            $result['street']['config']['template'] = 'Bleez_Pagseguro/group';
            foreach($result['street']['children'] as $k => $child){
                if($k == 0) {
                    $result['street']['children'][$k]['label'] = $result['street']['label']->getText();
                    $result['street']['children'][$k]['additionalClasses'] = 'required';
                }else if($k == 3){
                    $result['street']['children'][$k]['label'] = $this->fieldNames[$k];
                }else{
                    $result['street']['children'][$k]['validation']['required-entry'] = true;
                    $result['street']['children'][$k]['label'] = $this->fieldNames[$k];
                    $result['street']['children'][$k]['additionalClasses'] = 'required';
                }
            }
        }

        if(isset($result['vat_id'])){
            $result['vat_id']['validation']['required-entry'] = true;
        }



        return $result;
    }
}
