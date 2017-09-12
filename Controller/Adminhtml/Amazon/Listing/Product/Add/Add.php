<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class Add extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('listing_id');
        $listing =  $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);

        $productsIds = $this->getRequest()->getParam('products');
        $productsIds = explode(',', $productsIds);
        $productsIds = array_unique($productsIds);

        $listingProductIds = array();
        if (count($productsIds) > 0) {
            foreach ($productsIds as $productId) {
                if ($productId == '' || $productsIds[0] == 'true') {
                    continue;
                }

                $tempResult = $listing->addProduct($productId, \Ess\M2ePro\Helper\Data::INITIATOR_USER);
                if ($tempResult instanceof \Ess\M2ePro\Model\Listing\Product) {
                    $listingProductIds[] = $tempResult->getId();
                }
            }
        }

        $tempProducts = $this->getHelper('Data\Session')->getValue('temp_products');
        $tempProducts = array_merge((array)$tempProducts, $listingProductIds);
        $this->getHelper('Data\Session')->setValue('temp_products', $tempProducts);

        $isLastPart = $this->getRequest()->getParam('is_last_part');
        if ($isLastPart == 'yes') {

            $backUrl = $this->getUrl('*/*/index', array(
                'id' => $listingId,
                'skip_products_steps' => empty($tempProducts),
                'step' => 3,
                'wizard' => $this->getRequest()->getParam('wizard')
            ));

            $this->clearSession();

            $this->setJsonContent(['redirect' => $backUrl]);

            return $this->getResult();
        }

        $response = array('redirect' => '');
        $this->setJsonContent($response);

        return $this->getResult();
    }

    //########################################
}