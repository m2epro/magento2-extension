<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\Grid;

use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType as DictionaryProductTypeResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType as TemplateProductTypeResource;
use Magento\Framework\Api\Search\SearchResultInterface;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel implements
    SearchResultInterface
{
    use \Ess\M2ePro\Model\ResourceModel\Ui\Grid\SearchResultTrait;

    private DictionaryProductTypeResource $dictionaryProductTypeResource;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource;

    private bool $isMarketplaceTableJoined = false;

    public function __construct(
        DictionaryProductTypeResource $dictionaryProductTypeResource,
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
        $this->dictionaryProductTypeResource = $dictionaryProductTypeResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->prepareCollection();
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
            TemplateProductTypeResource::class,
        );
    }

    private function prepareCollection(): void
    {
        $this->getSelect()->join(
            ['adpt' => $this->dictionaryProductTypeResource->getMainTable()],
            sprintf(
                'adpt.%s = main_table.%s',
                DictionaryProductTypeResource::COLUMN_ID,
                TemplateProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID
            ),
            []
        );

        $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $this->getSelect()->columns([
            'id' => sprintf('main_table.%s', TemplateProductTypeResource::COLUMN_ID),
            'template_title' => sprintf('main_table.%s', TemplateProductTypeResource::COLUMN_TITLE),
            'marketplace_id' => sprintf('adpt.%s', DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID),
            'update_date' => sprintf('main_table.%s', TemplateProductTypeResource::COLUMN_UPDATE_DATE),
            'create_date' => sprintf('main_table.%s', TemplateProductTypeResource::COLUMN_CREATE_DATE),
            'invalid' => sprintf('adpt.%s', DictionaryProductTypeResource::COLUMN_INVALID),
            'out_of_date' => new \Magento\Framework\DB\Sql\Expression(
                sprintf(
                    'adpt.%s > adpt.%s',
                    DictionaryProductTypeResource::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
                    DictionaryProductTypeResource::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE
                )
            ),
        ]);
    }

    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        if ($field === 'marketplace') {
            $this->joinMarketplaceTable();

            $field = 'marketplace.' . \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_TITLE;
        }

        return parent::setOrder($field, $direction);
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'template_title') {
            $field = 'main_table.' . DictionaryProductTypeResource::COLUMN_TITLE;
        }

        parent::addFieldToFilter($field, $condition);

        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    private function joinMarketplaceTable(): void
    {
        if ($this->isMarketplaceTableJoined) {
            return;
        }

        $this->getSelect()->join(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            sprintf(
                'adpt.%s = marketplace.%s AND marketplace.%s = "%s"',
                DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID,
                \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_ID,
                \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_COMPONENT_MODE,
                \Ess\M2ePro\Helper\Component\Amazon::NICK
            ),
            []
        );

        $this->isMarketplaceTableJoined = true;
    }
}
