<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetCategorySpecificHtml as ParentBlock;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Chooser\Specific\Edit;

class GetCategorySpecificHtml extends ParentBlock
{
    public function execute()
    {
        $specific = $this->getLayout()->createBlock(
            Edit::class,
            '',
            [
                'data' => [
                    'selected_specifics' => $this->getRequest()->getParam('selected_specifics'),
                    'marketplace_id' => $this->getRequest()->getParam('marketplace_id'),
                    'template_id' => $this->getRequest()->getParam('template_id'),
                    'category_mode' => $this->getRequest()->getParam('category_mode'),
                    'category_value' => $this->getRequest()->getParam('category_value'),
                ],
            ]
        );

        $specific->prepareFormData();

        $this->setAjaxContent($specific->toHtml());

        return $this->getResult();
    }
}
