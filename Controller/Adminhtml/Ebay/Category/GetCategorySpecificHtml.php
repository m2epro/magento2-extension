<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetCategorySpecificHtml
 */
class GetCategorySpecificHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    //########################################

    public function execute()
    {
        /** @var $specific \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Edit */
        $specific = $this->createBlock(
            'Ebay_Template_Category_Chooser_Specific_Edit',
            '',
            [
                'data' => [
                    'selected_specifics' => $this->getRequest()->getParam('selected_specifics'),
                    'marketplace_id'     => $this->getRequest()->getParam('marketplace_id'),
                    'template_id'        => $this->getRequest()->getParam('template_id'),
                    'category_mode'      => $this->getRequest()->getParam('category_mode'),
                    'category_value'     => $this->getRequest()->getParam('category_value')
                ]
            ]
        );

        $specific->prepareFormData();

        $this->setAjaxContent($specific->toHtml());

        return $this->getResult();
    }

    //########################################
}
