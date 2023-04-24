<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    /** @var bool */
    private $isDictionaryTableAppended = false;
    /** @var bool */
    private $isMarketplaceTableAppended = false;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType */
    private $dictionaryResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType $dictionaryResource
     * @param \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType $dictionaryResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $helperFactory,
            $activeRecordFactory,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->dictionaryResource = $dictionaryResource;
        $this->marketplaceResource = $marketplaceResource;
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Template\ProductType::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType::class
        );
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function appendTableDictionary(): self
    {
        if ($this->isDictionaryTableAppended) {
            return $this;
        }

        $this->getSelect()->join(
            ['adpt' => $this->dictionaryResource->getMainTable()],
            'adpt.id=main_table.dictionary_product_type_id',
            ['product_type_title' => 'adpt.title']
        );

        $this->isDictionaryTableAppended = true;
        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function appendTableMarketplace(): self
    {
        if ($this->isMarketplaceTableAppended) {
            return $this;
        }

        $this->getSelect()->join(
            ['m' => $this->marketplaceResource->getMainTable()],
            'm.id=adpt.marketplace_id',
            ['marketplace_title' => 'm.title']
        );

        $this->isMarketplaceTableAppended = true;
        return $this;
    }

    /**
     * @param int $marketplaceId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function appendFilterMarketplaceId(int $marketplaceId): self
    {
        $this->appendTableDictionary();
        $this->getSelect()->where('adpt.marketplace_id = ?', $marketplaceId);
        return $this;
    }

    /**
     * @param string $nick
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function appendFilterNick(string $nick): self
    {
        $this->appendTableDictionary();
        $this->getSelect()->where('adpt.nick = ?', $nick);
        return $this;
    }
}
