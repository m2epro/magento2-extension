<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode as SourceModeBlock;

class RemoveAddedProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    public function execute()
    {
        $this->deleteListingProducts($this->getListing()->getSetting('additional_data', 'adding_listing_products_ids'));

        if ($this->getListing()->getSetting('additional_data', 'source') == SourceModeBlock::MODE_OTHER) {

            $additionalData = $this->getListing()->getSettings('additional_data');
            unset($additionalData['source']);
            $this->getListing()->setSettings('additional_data', $additionalData)->save();

            return $this->_redirect('*/walmart_listing_other/view', array(
                'account'     => $this->getListing()->getAccountId(),
                'marketplace' => $this->getListing()->getMarketplaceId(),
            ));
        }

        return $this->_redirect('*/walmart_listing_product_add/index', array(
            'step' => 2,
            'id' => $this->getRequest()->getParam('id'),
            '_query' => [
                'source' => $this->getHelper('Data\Session')->getValue('products_source')
            ]
        ));
    }
}