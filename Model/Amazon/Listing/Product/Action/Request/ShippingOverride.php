<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request;

class ShippingOverride extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel
{
    const TYPE_EXCLUSIVE = 'Exclusive';
    const TYPE_ADDITIVE = 'Additive';

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride
     */
    private $shippingOverrideTemplate = NULL;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        if (!$this->getConfigurator()->isShippingOverrideAllowed()) {
            return array();
        }

        if (!$this->getAmazonListingProduct()->getAmazonAccount()->isShippingModeOverride()) {
            return array();
        }

        if (!$this->getAmazonListingProduct()->isExistShippingOverrideTemplate()) {
            return array();
        }

        $data = array();

        foreach ($this->getShippingOverrideTemplate()->getServices(true) as $service) {

            /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service $service */

            $tempService = array(
                'option' => $service->getOption()
            );

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
        if (is_null($this->shippingOverrideTemplate)) {
            $this->shippingOverrideTemplate = $this->getAmazonListingProduct()->getShippingOverrideTemplate();
        }
        return $this->shippingOverrideTemplate;
    }

    //########################################
}