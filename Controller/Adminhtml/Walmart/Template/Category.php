<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class Category
 * @package Ess\M2ePro\Controller\Adminhtml\Walmart\Template
 */
abstract class Category extends Template
{
    // ---------------------------------------

    protected function formatCategoryRow(&$row)
    {
        $row['product_data_nicks'] = $row['product_data_nicks'] !== null
            ? (array)$this->getHelper('Data')->jsonDecode($row['product_data_nicks']) : [];
    }

    protected function prepareGridBlock()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add\Grid $grid */
        $grid = $this->createBlock(
            'Walmart_Template_Category_Categories_Specific_Add_Grid'
        );

        $grid->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));
        $grid->setProductDataNick($this->getRequest()->getParam('product_data_nick'));
        $grid->setCurrentXpath($this->getRequest()->getParam('current_indexed_xpath'));
        $grid->setRenderedSpecifics(
            (array)$this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('rendered_specifics'))
        );
        $grid->setSelectedSpecifics(
            (array)$this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('selected_specifics'))
        );
        $grid->setOnlyDesired($this->getRequest()->getParam('only_desired'), false);
        $grid->setSearchQuery($this->getRequest()->getParam('query'));

        return $grid;
    }

    //########################################
}
