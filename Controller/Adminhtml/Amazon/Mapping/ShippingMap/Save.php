<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Mapping\ShippingMap;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Mapping
{
    /** @var \Ess\M2ePro\Model\Amazon\ShippingMapFactory */
    protected $amazonShippingMapFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap */
    protected $amazonShippingMapResource;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $helper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context                       $context,
        \Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap             $amazonShippingMapResource,
        \Ess\M2ePro\Model\Amazon\ShippingMapFactory              $amazonShippingMapFactory,
        \Ess\M2ePro\Helper\Component\Amazon                            $helper
    ) {
        parent::__construct($amazonFactory, $context);
        $this->helper = $helper;
        $this->amazonShippingMapResource = $amazonShippingMapResource;
        $this->amazonShippingMapFactory = $amazonShippingMapFactory;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }
        $this->saveShippingMappingData($post);
        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }

    /**
     * @param $postData
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function saveShippingMappingData($postData)
    {
        if (!isset($postData['amazon_shipping']) || !is_array($postData['amazon_shipping'])) {
            return;
        }

        foreach ($postData['amazon_shipping'] as $marketplaceId => $marketplaceData) {
            foreach ($marketplaceData as $location => $locationData) {
                foreach ($locationData as $amazonCode => $map) {
                    $this->saveSingleShippingMapping($amazonCode, $map, $marketplaceId, $location);
                }
            }
        }
    }

    /**
     * @param $amazonCode
     * @param $map
     * @param $marketplaceId
     * @param $location
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveSingleShippingMapping($amazonCode, $map, $marketplaceId, $location)
    {
        $magentoCode = $map['magento_code'];
        if (empty($amazonCode) || empty($magentoCode)) {
            return;
        }

        $amazonShippingMap = $this->helper->getAmazonShippingMap($amazonCode, $marketplaceId, $location);
        if ($amazonShippingMap->getId()) {
            $amazonShippingMap->setMagentoCode($magentoCode);
        } else {
            $amazonShippingMap->setAmazonCode($amazonCode)
                              ->setMagentoCode($magentoCode)
                              ->setMarketplaceId($marketplaceId)
                              ->setLocation($location);
        }
        $this->amazonShippingMapResource->save($amazonShippingMap);
    }
}
