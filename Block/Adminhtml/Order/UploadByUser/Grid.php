<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\UploadByUser;

use Ess\M2ePro\Model\Cron\Task\Amazon\Order\UploadByUser\Manager as AmazonManager;
use Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser\Manager as EbayManager;
use Ess\M2ePro\Model\Cron\Task\Walmart\Order\UploadByUser\Manager as WalmartManager;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory */
    protected $customCollectionFactory;

    /** @var string */
    protected $_component;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->customCollectionFactory = $customCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('orderUploadByUserPopupGrid');

        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->customCollectionFactory->create();

        foreach ($this->getAccountsCollection()->getItems() as $id => $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            $manager = $this->getManager($account);
            $item = new \Magento\Framework\DataObject(
                [
                    'title'      => $account->getTitle(),
                    'identifier' => $manager->getIdentifier(),
                    'from_date'  => $manager->getFromDate() ? $manager->getFromDate()->format('Y-m-d H:i:s') : null,
                    'to_date'    => $manager->getToDate() ? $manager->getToDate()->format('Y-m-d H:i:s') : null,

                    '_manager_' => $manager,
                    '_account_' => $account
                ]
            );
            $collection->addItem($item);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if ($this->_component !== \Ess\M2ePro\Helper\Component\Amazon::NICK) {
            $this->addColumn(
                'title',
                [
                    'header'   => $this->__('Title'),
                    'align'    => 'left',
                    'width'    => '300px',
                    'type'     => 'text',
                    'sortable' => false,
                    'index'    => 'title',
                ]
            );
        }

        $this->addColumn(
            'identifier',
            [
                'header'   => $this->getIdentifierTitle(),
                'align'    => 'left',
                'width'    => '300px',
                'type'     => 'text',
                'sortable' => false,
                'index'    => 'identifier',
            ]
        );

        $this->addColumn(
            'from_date',
            [
                'header'   => $this->__('From Date'),
                'align'    => 'left',
                'width'    => '200px',
                'index'    => 'from_date',
                'sortable' => false,
                'type'     => 'datetime',
                'format'   => \IntlDateFormatter::MEDIUM,
                'frame_callback' => [$this, 'callbackColumnDate']
            ]
        );

        $this->addColumn(
            'to_date',
            [
                'header'   => $this->__('To Date'),
                'align'    => 'left',
                'width'    => '200px',
                'index'    => 'to_date',
                'type'     => 'datetime',
                'sortable' => false,
                'format'   => \IntlDateFormatter::MEDIUM,
                'frame_callback' => [$this, 'callbackColumnDate']
            ]
        );

        $this->addColumn(
            'action',
            [
                'header'         => $this->__('Action'),
                'width'          => '80px',
                'type'           => 'text',
                'align'          => 'right',
                'sortable'       => false,
                'frame_callback' => [$this, 'callbackColumnAction']
            ]
        );

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnDate($value, $row, $column, $isExport)
    {
        /** @var AmazonManager|EbayManager|WalmartManager $manager */
        $manager = $row['_manager_'];

        if ($manager->isEnabled()) {
            return $value;
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $row['_account_'];

        return <<<HTML
<script>

require([
    'mage/calendar'
], function () {
    jQuery('#{$account->getId()}_{$column->getIndex()}').calendar({
        showsTime: true,
        dateFormat: "yy-mm-dd",
        timeFormat: 'HH:mm:00',
        showButtonPanel: false
    })
})

</script>

<form id="{$account->getId()}_{$column->getIndex()}_form">
    <input type="text" id="{$account->getId()}_{$column->getIndex()}" name="{$account->getId()}_{$column->getIndex()}"
           class="input-text admin__control-text required-entry validate-date" />
</form>
HTML;
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        /** @var AmazonManager|EbayManager|WalmartManager $manager */
        $manager = $row['_manager_'];

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $row['_account_'];

        $data = [
            'label'   => $manager->isEnabled()
                ? $this->__('Cancel')
                : $this->__('Reimport'),

            'onclick' => $manager->isEnabled()
                ? "UploadByUserObj.resetUpload({$account->getId()})"
                : "UploadByUserObj.configureUpload({$account->getId()})",

            'class' => 'action primary'
        ];

        $state = '';
        if ($manager->isEnabled()) {
            $state = <<<HTML
<br/>
<span style="color: orange; font-style: italic;">{$this->__('(in progress)')}</span>
HTML;
        }

        $button = $this->createBlock('Magento\Button')->setData($data);
        return $button->toHtml() . $state;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
     */
    protected function getAccountsCollection()
    {
        switch ($this->_component) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account')
                                                  ->getCollection();
                $collection->getSelect()->group(['second_table.merchant_id']);
                return $collection;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account')
                                                  ->getCollection();
                return $collection;

            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Walmart::NICK, 'Account')
                                                  ->getCollection();
                return $collection;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Component is not set');
    }

    protected function getIdentifierTitle()
    {
        switch ($this->_component) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->__('Merchant ID');

            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                return $this->__('User ID');

            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->__('Consumer ID');
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Component is not set');
    }

    protected function getManager(\Ess\M2ePro\Model\Account $account)
    {
        switch ($account->getComponentMode()) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $manager = $this->modelFactory->getObject('Cron_Task_Amazon_Order_UploadByUser_Manager');
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $manager = $this->modelFactory->getObject('Cron_Task_Ebay_Order_UploadByUser_Manager');
                break;

            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $manager = $this->modelFactory->getObject('Cron_Task_Walmart_Order_UploadByUser_Manager');
                break;
        }

        /** @var AmazonManager|EbayManager|WalmartManager $manager */
        $manager->setIdentifierByAccount($account);
        return $manager;
    }

    //########################################

    public function getRowUrl($row)
    {
        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl(
            '*/order_uploadByUser/getPopupGrid',
            ['component' => $this->_component]
        );
    }

    //########################################

    public function setComponent($component)
    {
        $this->_component = $component;
    }

    //########################################
}
