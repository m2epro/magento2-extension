<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form;

class Charity extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $enabledMarketplaces = null;

    protected $_template = 'ebay/template/selling_format/charity.phtml';

    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data);
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData([
                'label'   => $this->__('Add Charity'),
                'onclick' => 'EbayTemplateSellingFormatObj.addCharityRow();',
                'class' => 'action primary add_charity_button'
            ]);
        $this->setChild('add_charity_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData([
                'label'   => $this->__('Remove'),
                'onclick' => 'EbayTemplateSellingFormatObj.removeCharityRow(this);',
                'class' => 'delete icon-btn remove_charity_button'
            ]);
        $this->setChild('remove_charity_button', $buttonBlock);
        // ---------------------------------------
    }

    public function getEnabledMarketplaces()
    {
        if ($this->enabledMarketplaces === null) {
            if ($this->getData('marketplace') !== null) {
                $this->enabledMarketplaces = [$this->getData('marketplace')];
            } else {
                $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
                $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
                $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
                $collection->setOrder('sorder', 'ASC');

                $this->enabledMarketplaces = $collection->getItems();
            }
        }

        return $this->enabledMarketplaces;
    }

    public function getCharityDictionary()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableDictMarketplace = $this->databaseHelper
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');

        $dbSelect = $connection->select()
            ->from($tableDictMarketplace, ['marketplace_id', 'charities']);

        $data = $connection->fetchAssoc($dbSelect);

        foreach ($data as $key => $item) {
            $data[$key]['charities'] = $this->dataHelper->jsonDecode($item['charities']);
        }

        return $data;
    }
}
