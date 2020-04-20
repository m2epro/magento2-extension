<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description
 */
abstract class Description extends Template
{
    // ---------------------------------------

    protected function formatCategoryRow(&$row)
    {
        $row['product_data_nicks'] = $row['product_data_nicks'] !== null
            ? (array)$this->getHelper('Data')->jsonDecode($row['product_data_nicks']) : [];
    }

    protected function prepareGridBlock()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Specific\Add\Grid $grid */
        $grid = $this->createBlock(
            'Amazon_Template_Description_Category_Specific_Add_Grid'
        );

        $grid->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));
        $grid->setProductDataNick($this->getRequest()->getParam('product_data_nick'));
        $grid->setCurrentXpath($this->getRequest()->getParam('current_indexed_xpath'));
        $grid->setAllRenderedSpecifics(
            (array)$this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('all_rendered_specifics'))
        );
        $grid->setBlockRenderedSpecifics(
            (array)$this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('block_rendered_specifics'))
        );
        $grid->setSelectedSpecifics(
            (array)$this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('selected_specifics'))
        );
        $grid->setOnlyDesired((bool)$this->getRequest()->getParam('only_desired'));
        $grid->setSearchQuery($this->getRequest()->getParam('query'));

        return $grid;
    }

    //########################################
}
