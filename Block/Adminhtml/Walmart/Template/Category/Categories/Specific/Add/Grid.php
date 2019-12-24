<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $customCollectionFactory;
    protected $resourceConnection;

    public $marketplaceId;
    public $productDataNick;

    public $currentXpath;

    public $searchQuery;
    public $onlyDesired = false;

    public $selectedSpecifics = [];

    public $allRenderedSpecifics = [];
    public $blockRenderedSpecifics = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryCategoriesSpecificAddGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $select = $this->resourceConnection->getConnection()->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')
                      ->getTableNameWithPrefix('m2epro_walmart_dictionary_specific')
              )
              ->where('marketplace_id = ?', (int)$this->marketplaceId)
              ->where('product_data_nick = ?', $this->productDataNick)
              ->where('type != ?', \Ess\M2ePro\Model\Walmart\Template\Category\Specific::DICTIONARY_TYPE_CONTAINER)
              ->where('xpath LIKE ?', "{$this->currentXpath}/%")
              ->order('title ASC');

        if ($this->searchQuery) {
            $select->where('title LIKE ?', "%{$this->searchQuery}%");
        }

        $filteredResult = [];

        $queryStmt = $select->query();
        while ($row = $queryStmt->fetch()) {
            if (in_array($row['xpath'], $this->selectedSpecifics, true)) {
                continue;
            }

            if (in_array($row['xpath'], $this->allRenderedSpecifics, true)) {
                // an already rendered specific can be added again only to parent container directly
                if (str_replace($this->currentXpath . '/', '', $row['xpath']) !== $row['xml_tag']) {
                    continue;
                }

                if (in_array($row['xpath'], $this->blockRenderedSpecifics, true)) {
                    continue;
                }
            }

            $row['data_definition'] = (array)$this->getHelper('Data')->jsonDecode($row['data_definition']);
            $row['is_desired'] = !empty($row['data_definition']['is_desired']) && $row['data_definition']['is_desired'];

            if ($this->onlyDesired && !$row['is_desired']) {
                continue;
            }

            $filteredResult[] = $row;
        }

        usort($filteredResult, function ($a, $b) {

            if ($a['is_desired'] && !$b['is_desired']) {
                return -1;
            }

            if ($b['is_desired'] && !$a['is_desired']) {
                return 1;
            }

            return $a['title'] == $b['title'] ? 0 : ($a['title'] > $b['title'] ? 1 : -1);
        });

        $collection = $this->customCollectionFactory->create();
        foreach ($filteredResult as $item) {
            $collection->addItem(new \Magento\Framework\DataObject($item));
        }
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header'         => $this->__('Specific'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'title',
            'width'          => '700px',
            'escape'         => false,
            'filter'         => false,
            'sortable'       => false,
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('is_desired', [
            'header'         => $this->__('Desired'),
            'align'          => 'center',
            'type'           => 'text',
            'index'          => 'is_desired',
            'width'          => '80px',
            'filter'         => false,
            'sortable'       => false,
            'frame_callback' => [$this, 'callbackColumnIsDesired']
        ]);

        $this->addColumn('actions', [
            'header'         => $this->__('Action'),
            'align'          => 'center',
            'type'           => 'text',
            'width'          => '80px',
            'filter'         => false,
            'sortable'       => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);
    }

    //########################################

    public function callbackColumnTitle($title, $row, $column, $isExport)
    {
        strlen($title) > 60 && $title = substr($title, 0, 60) . '...';
        $title = $this->getHelper('Data')->escapeHtml($title);

        $path = explode('/', ltrim($row->getData('xpath'), '/'));
        array_pop($path);
        $path = implode(' > ', $path);
        $path = $this->getHelper('Data')->escapeHtml($path);

        $fullPath = $path;
        strlen($path) > 135 && $path = substr($path, 0, 135) . '...';

        $foundInWord = $this->__('Found In: ');

        return <<<HTML
<div style="margin-left: 3px">
<a href="javascript:void(0);" class="specific_search_result_row" xpath ="{$row->getData('xpath')}"
                                                                 xml_tag = {$row->getData('xml_tag')}>
    {$title}
</a><br/>
<span style="font-weight: bold;">{$foundInWord}</span>&nbsp;
<span title="{$fullPath}">{$path}</span><br/>
</div>
HTML;
    }

    public function callbackColumnIsDesired($value, $row, $column, $isExport)
    {
        return $value ? $this->__('Yes') : $this->__('No');
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $select = $this->__('Select');
        return <<<HTML
<a href="javascript:void(0);" class="specific_search_result_row" xpath = {$row->getData('xpath')}
                                                                 xml_tag = {$row->getData('xml_tag')}>
{$select}
</a>
HTML;
    }

    //########################################

    public function setMarketplaceId($marketplaceId)
    {
        $this->marketplaceId = $marketplaceId;
        return $this;
    }

    public function setProductDataNick($productDataNick)
    {
        $this->productDataNick = $productDataNick;
        return $this;
    }

    public function setCurrentXpath($indexedXpath)
    {
        $this->currentXpath = preg_replace('/-\d+/', '', $indexedXpath);
        return $this;
    }

    public function setAllRenderedSpecifics(array $specifics)
    {
        $this->allRenderedSpecifics = $this->replaceWithDictionaryXpathes($specifics);
        return $this;
    }

    public function setBlockRenderedSpecifics(array $specifics)
    {
        $this->blockRenderedSpecifics = $this->replaceWithDictionaryXpathes($specifics);
        return $this;
    }

    public function setSelectedSpecifics(array $specifics)
    {
        $this->selectedSpecifics = $this->replaceWithDictionaryXpathes($specifics);
        return $this;
    }

    public function setOnlyDesired($value)
    {
        $this->onlyDesired = (bool)$value;
        return $this;
    }

    public function setSearchQuery($searchQuery)
    {
        $this-> searchQuery = $searchQuery;
        return $this;
    }

    // ---------------------------------------

    private function replaceWithDictionaryXpathes(array $xPathes)
    {
        return array_map(function ($el) {
            return preg_replace('/-\d+/', '', $el);
        }, $xPathes);
    }

    //########################################

    public function getGridUrl()
    {
        return false;
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}
