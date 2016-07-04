<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Grid;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid as WidgetAbstractGrid;

abstract class AbstractGrid extends WidgetAbstractGrid
{
    const LISTING_ID_FIELD = 'listing_id';
    const LISTING_PRODUCT_ID_FIELD = 'listing_product_id';
    const LISTING_PARENT_PRODUCT_ID_FIELD = 'parent_listing_product_id';

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setCustomPageSize(true);
    }

    //########################################

    protected function getEntityId()
    {
        if ($this->isListingLog()) {
            return $this->getRequest()->getParam('id');
        }

        if ($this->isListingProductLog()) {
            return $this->getRequest()->getParam('listing_product_id');
        }

        return NULL;
    }

    protected function getEntityField()
    {
        if ($this->isListingLog()) {
            return self::LISTING_ID_FIELD;
        }

        if ($this->isListingProductLog()) {
            return self::LISTING_PRODUCT_ID_FIELD;
        }

        return NULL;
    }

    protected function getActionName()
    {
        return 'grid';
    }

    //########################################

    public function isListingLog()
    {
        $id = $this->getRequest()->getParam('id');
        return !empty($id);
    }

    public function isListingProductLog()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        return !empty($listingProductId);
    }

    //########################################

    public function getListingProductId()
    {
        return $this->getRequest()->getParam('listing_product_id', false);
    }

    // ---------------------------------------

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = NULL;

    /**
     * @return \Ess\M2ePro\Model\Listing\Product|null
     */
    public function getListingProduct()
    {
        if (is_null($this->listingProduct)) {
            $this->listingProduct = $this->activeRecordFactory->getObjectLoaded(
                'Listing\Product', $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    //########################################

    protected function _getLogTypeList()
    {
        return array(
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE => $this->__('Notice'),
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_SUCCESS => $this->__('Success'),
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_WARNING => $this->__('Warning'),
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR => $this->__('Error')
        );
    }

    protected function _getLogPriorityList()
    {
        return array(
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_HIGH => $this->__('High'),
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM => $this->__('Medium'),
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_LOW => $this->__('Low')
        );
    }

    protected function _getLogInitiatorList()
    {
        return array(
            \Ess\M2ePro\Helper\Data::INITIATOR_USER => $this->__('Manual'),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION => $this->__('Automatic')
        );
    }

    //########################################

    public function callbackColumnType($value, $row, $column, $isExport)
    {
         switch ($row->getData('type')) {

            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE:
                break;

            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_SUCCESS:
                $value = '<span style="color: green;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_WARNING:
                $value = '<span style="color: orange; font-weight: bold;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR:
                 $value = '<span style="color: red; font-weight: bold;">'.$value.'</span>';
                break;

            default:
                break;
        }

        return $value;
    }

    public function callbackColumnInitiator($value, $row, $column, $isExport)
    {
        return "<span style='padding: 0 10px;'>{$value}</span>";
    }

    public function callbackDescription($value, $row, $column, $isExport)
    {
        $fullDescription = $this->getHelper('View')->getModifiedLogMessage($row->getData('description'));

        $row->setData('description', $fullDescription);
        $renderedText = $column->getRenderer()->render($row);

        $fullDescription = $this->escapeHtml($fullDescription);

        if (strlen($fullDescription) == strlen($renderedText)) {
            return $renderedText;
        }

        $row->setData('description', strip_tags($fullDescription));
        $renderedText = $column->getRenderer()->render($row);

        $renderedText .= '&nbsp;(<a href="javascript:void(0)" onclick="LogObj.showFullText(this);">more</a>)
                          <div style="display: none;"><br/>'.$fullDescription.'<br/><br/></div>';

        return $renderedText;
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('log/grid.css');
        parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'Description' => $this->__('Description')
        ]);

        $this->js->addRequireJs(['l' => 'M2ePro/Log'], "window.LogObj = new Log();");

        return parent::_toHtml();
    }

    //########################################
}