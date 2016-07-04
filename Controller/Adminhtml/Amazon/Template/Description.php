<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

abstract class Description extends Template
{
    // ---------------------------------------

    protected function formatCategoryRow(&$row)
    {
        $row['product_data_nicks'] = !is_null($row['product_data_nicks'])
            ? (array)json_decode($row['product_data_nicks'], true) : array();
    }

    protected function prepareGridBlock()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Specific\Add\Grid $grid */
        $grid = $this->createBlock(
            'Amazon\Template\Description\Category\Specific\Add\Grid'
        );

        $grid->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));
        $grid->setProductDataNick($this->getRequest()->getParam('product_data_nick'));
        $grid->setCurrentXpath($this->getRequest()->getParam('current_indexed_xpath'));
        $grid->setRenderedSpecifics((array)json_decode($this->getRequest()->getParam('rendered_specifics'), true));
        $grid->setSelectedSpecifics((array)json_decode($this->getRequest()->getParam('selected_specifics'), true));
        $grid->setOnlyDesired($this->getRequest()->getParam('only_desired'), false);
        $grid->setSearchQuery($this->getRequest()->getParam('query'));

        return $grid;
    }

    //########################################
}