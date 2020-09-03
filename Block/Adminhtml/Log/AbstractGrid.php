<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid as WidgetAbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
 */
abstract class AbstractGrid extends WidgetAbstractGrid
{
    const LISTING_ID_FIELD                = 'listing_id';
    const LISTING_PRODUCT_ID_FIELD        = 'listing_product_id';
    const LISTING_PARENT_PRODUCT_ID_FIELD = 'parent_listing_product_id';

    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = null;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

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
            return $this->getRequest()->getParam($this::LISTING_ID_FIELD);
        }

        if ($this->isListingProductLog()) {
            return $this->getRequest()->getParam($this::LISTING_PRODUCT_ID_FIELD);
        }

        return null;
    }

    protected function getEntityField()
    {
        if ($this->isListingLog()) {
            return $this::LISTING_ID_FIELD;
        }

        if ($this->isListingProductLog()) {
            return $this::LISTING_PRODUCT_ID_FIELD;
        }

        return null;
    }

    //########################################

    public function isListingLog()
    {
        $id = $this->getRequest()->getParam($this::LISTING_ID_FIELD);
        return !empty($id);
    }

    public function isListingProductLog()
    {
        $listingProductId = $this->getRequest()->getParam($this::LISTING_PRODUCT_ID_FIELD);
        return !empty($listingProductId);
    }

    //########################################

    public function getListingProductId()
    {
        return $this->getRequest()->getParam($this::LISTING_PRODUCT_ID_FIELD, false);
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product|null
     */
    public function getListingProduct()
    {
        if ($this->listingProduct === null) {
            $this->listingProduct = $this->activeRecordFactory->getObjectLoaded(
                'Listing\Product',
                $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    //########################################

    protected function _setCollectionOrder($column)
    {
        // We need to sort by id to maintain the correct sequence of records
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
            $collection->getSelect()->order($columnIndex . ' ' . strtoupper($column->getDir()))->order('id DESC');
        }

        return $this;
    }

    //########################################

    protected function _getLogTypeList()
    {
        return [
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE  => $this->__('Notice'),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS => $this->__('Success'),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING => $this->__('Warning'),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR   => $this->__('Error')
        ];
    }

    protected function _getLogInitiatorList()
    {
        return [
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN   => $this->__('Unknown'),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER      => $this->__('Manual'),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION => $this->__('Automatic')
        ];
    }

    //########################################

    public function callbackColumnType($value, $row, $column, $isExport)
    {
        switch ($row->getData('type')) {
            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE:
                break;

            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS:
                $value = '<span style="color: green;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING:
                $value = '<span style="color: orange; font-weight: bold;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Synchronization\Log::TYPE_FATAL_ERROR:
            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR:
                 $value = '<span style="color: red; font-weight: bold;">'.$value.'</span>';
                break;

            default:
                break;
        }

        return $value;
    }

    public function callbackColumnInitiator($value, $row, $column, $isExport)
    {
        $initiator = $row->getData('initiator');

        switch ($initiator) {
            case \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION:
                $message = "<span style=\"text-decoration: underline;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN:
                $message = "<span style=\"font-style: italic; color: gray;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Helper\Data::INITIATOR_USER:
            default:
                $message = "<span>{$value}</span>";
                break;
        }

        return $message;
    }

    public function callbackColumnDescription($value, $row, $column, $isExport)
    {
        $fullDescription = str_replace(
            "\n",
            '<br>',
            $this->getHelper('View')->getModifiedLogMessage($row->getData('description'))
        );

        $renderedText = $this->stripTags($fullDescription, '<br>');
        if (strlen($renderedText) < 200) {
            return $fullDescription;
        }

        $renderedText = $this->filterManager->truncate($renderedText, ['length' => 200]);

        return <<<HTML
{$renderedText}
<a href="javascript://" onclick="LogObj.showFullText(this);">
    {$this->__('more')}
</a>
<div class="no-display">{$fullDescription}</div>
HTML;
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', [
            '_current' => true,
        ]);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('log/grid.css');
        $this->css->addFile('switcher.css');

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
