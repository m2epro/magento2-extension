<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\ShippingOverride
 */
class ShippingOverride extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel
{
    const TYPE_EXCLUSIVE = 'Exclusive';
    const TYPE_ADDITIVE = 'Additive';

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride
     */
    private $shippingOverrideTemplate = null;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        if (!$this->getConfigurator()->isShippingOverrideAllowed()) {
            return [];
        }

        if (!$this->getAmazonListingProduct()->getAmazonAccount()->isShippingModeOverride()) {
            return [];
        }

        if (!$this->getAmazonListingProduct()->isExistShippingOverrideTemplate()) {
            return [];
        }

        $data = [];

        foreach ($this->getShippingOverrideTemplate()->getServices(true) as $service) {

            /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service $service */

            $tempService = [
                'option' => $service->getOption()
            ];

            if ($service->isTypeRestrictive()) {
                $tempService['is_restricted'] = true;
            }

            if ($service->isTypeExclusive()) {
                $tempService['type'] = self::TYPE_EXCLUSIVE;
            }

            if ($service->isTypeAdditive()) {
                $tempService['type'] = self::TYPE_ADDITIVE;
            }

            if ($service->isTypeExclusive() || $service->isTypeAdditive()) {
                $store = $this->getListing()->getStoreId();
                $tempService['amount'] = $service->getSource($this->getMagentoProduct())->getCost($store);
            }

            $data['shipping_data'][] = $tempService;
        }

        return $data;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingOverride
     */
    private function getShippingOverrideTemplate()
    {
        if ($this->shippingOverrideTemplate === null) {
            $this->shippingOverrideTemplate = $this->getAmazonListingProduct()->getShippingOverrideTemplate();
        }
        return $this->shippingOverrideTemplate;
    }

    //########################################
}
