<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Bids;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    protected $bidsData;
    protected $listingProductId;

    protected $customCollectionFactory;
    protected $resourceConnection;
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->localeCurrency = $localeCurrency;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingProductBidsGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getBidsData()
    {
        return $this->bidsData;
    }

    /**
     * @param mixed $bidsData
     */
    public function setBidsData($bidsData)
    {
        $this->bidsData = $bidsData;
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->customCollectionFactory->create();
        $collection->setConnection($this->resourceConnection->getConnection());
        foreach ($this->getBidsData() as $index => $item) {
            $temp = array(
                'user_id' => $item['user']['user_id'],
                'email' => $item['user']['email'],
                'price' => $item['price'],
                'time' => $item['time']
            );

            $collection->addItem(new \Magento\Framework\DataObject($temp));
        }
        $collection->setCustomSize(count($this->getBidsData()));
        $this->setCollection($collection);

        parent::_prepareCollection();

        $collection->setCustomIsLoaded(true);

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('user_id', array(
            'header'       => $this->__('eBay User ID'),
            'width'        => '180px',
            'align'        => 'center',
            'type'         => 'text',
            'index'        => 'user_id',
            'sortable'     => false
        ));

        $this->addColumn('email', array(
            'header'       => $this->__('eBay User Email'),
            'width'        => '180px',
            'align'        => 'center',
            'type'         => 'text',
            'index'        => 'email',
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnEmail')
        ));

        $this->addColumn('price',array(
            'header'       => $this->__('Price'),
            'width'        => '90px',
            'align'        => 'right',
            'index'        => 'price',
            'sortable'     => false,
            'type'         => 'number',
            'frame_callback' => array($this, 'callbackColumnPrice')
        ));

        $this->addColumn('time', array(
            'header'       => $this->__('Date'),
            'width'        => '180px',
            'align'        => 'right',
            'type'         => 'datetime',
            'index'        => 'time',
            'sortable'     => false,
            'format'       => \IntlDateFormatter::MEDIUM,
        ));
    }

    //########################################

    public function callbackColumnEmail($value, $row, $column, $isExport)
    {
        if ($value == 'Invalid Request') {
            return '<span style="color: gray">' . $this->__('Not Available') . '</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $currency = $this->getListingProduct()->getMarketplace()->getChildObject()->getCurrency();
        $value = $this->localeCurrency->getCurrency($currency)->toCurrency($value);

        return '<div style="margin-right: 5px;">'.$value.'</div>';
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        $help = $this->createBlock('HelpBlock');
        $help->setData([
            'content' => $this->__(
                'In this section you can see the list of all Bids for your Product sorted by
                 descending. You can see the eBay User ID, User email, the Price and a Date of Bid Creation.'
            )
        ]);

        $html = parent::_toHtml();

        return <<<HTML
<div style="margin: 10px 0;">
{$help->toHtml()}
    <div style="height: 250px; overflow: auto;">
        {$html}
    </div>
</div>
HTML;

    }

    //########################################
}
