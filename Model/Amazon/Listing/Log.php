<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

class Log extends \Ess\M2ePro\Model\Listing\Log
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    //########################################

    /**
     * @param $listingId
     * @param $productId
     * @param $listingProductId
     * @param int $initiator
     * @param null $actionId
     * @param null $action
     * @param null $description
     * @param null $type
     * @param null $priority
     * @param array $additionalData
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductMessage($listingId,
                                      $productId,
                                      $listingProductId,
                                      $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL,
                                      array $additionalData = array())
    {
        $dataForAdd = $this->makeDataForAdd($listingId,
                                            $initiator,
                                            $productId,
                                            $listingProductId,
                                            $actionId,
                                            $action,
                                            $description,
                                            $type,
                                            $priority,
                                            $additionalData);

        if (!empty($listingProductId)) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product', $listingProductId
            );

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
            $variationManager = $listingProduct->getChildObject()->getVariationManager();

            if ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                $productOptions = $variationManager->getTypeModel()->getProductOptions();

                if (!empty($productOptions)) {
                    $dataForAdd['additional_data'] = (array)$this->getHelper('Data')->jsonDecode(
                        $dataForAdd['additional_data']
                    );
                    $dataForAdd['additional_data']['variation_options'] = $productOptions;
                    $dataForAdd['additional_data'] = $this->getHelper('Data')->jsonEncode(
                        $dataForAdd['additional_data']
                    );
                }
            }

            if ($variationManager->isRelationChildType()) {
                $dataForAdd['parent_listing_product_id'] = $variationManager->getVariationParentId();
            }
        }

        $this->createMessage($dataForAdd);
    }

    //########################################
}