<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

use Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Edit;

class GetCategorySpecificHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Edit $specific */
        $specific = $this->getLayout()->createBlock(
            Edit::class,
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
