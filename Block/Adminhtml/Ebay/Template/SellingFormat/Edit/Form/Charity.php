<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form;

class Charity extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $enabledMarketplaces = NULL;

    protected $_template = 'ebay/template/selling_format/charity.phtml';

    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Add Charity'),
                'onclick' => 'EbayTemplateSellingFormatObj.addCharityRow();',
                'class' => 'action primary add_charity_button'
            ));
        $this->setChild('add_charity_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Remove'),
                'onclick' => 'EbayTemplateSellingFormatObj.removeCharityRow(this);',
                'class' => 'delete icon-btn remove_charity_button'
            ));
        $this->setChild('remove_charity_button', $buttonBlock);
        // ---------------------------------------
    }

    public function getEnabledMarketplaces()
    {
        if (is_null($this->enabledMarketplaces)) {
            $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
            $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
            $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $collection->setOrder('sorder', 'ASC');

            $this->enabledMarketplaces = $collection->getItems();
        }

        return $this->enabledMarketplaces;
    }

    public function getCharityDictionary()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableDictMarketplace = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_marketplace');

        $dbSelect = $connection->select()
            ->from($tableDictMarketplace, ['marketplace_id', 'charities']);

        $data = $connection->fetchAssoc($dbSelect);

        foreach ($data as $key => $item) {
            $data[$key]['charities'] = $this->getHelper('Data')->jsonDecode($item['charities']);
        }

        return $data;
    }
}