<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use \Magento\Backend\App\Action;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SellingFormat\GetSearchCharityPopUpHtml
 */
class GetSearchCharityPopUpHtml extends Template
{
    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if (empty($marketplaceId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Charity\Search $charitySearchBlock */
        $charitySearchBlock = $this->createBlock('Ebay_Template_SellingFormat_Edit_Form_Charity_Search');
        $charitySearchBlock->setData('marketplace_id', $marketplaceId);

        $this->setAjaxContent($charitySearchBlock);

        return $this->getResult();
    }

    //########################################
}
