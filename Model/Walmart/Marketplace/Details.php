<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Marketplace;

/**
 * Class \Ess\M2ePro\Model\Walmart\Marketplace\Details
 */
class Details extends \Ess\M2ePro\Model\AbstractModel
{
    private $resourceConnection;

    private $marketplaceId = null;

    private $productData = [];

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @param $marketplaceId
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function setMarketplaceId($marketplaceId)
    {
        if ($this->marketplaceId === $marketplaceId) {
            return $this;
        }

        $this->marketplaceId = $marketplaceId;
        $this->load();

        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getProductData()
    {
        return $this->productData;
    }

    /**
     * @param $productDataNick
     * @return array
     */
    public function getVariationAttributes($productDataNick)
    {
        if (!isset($this->productData[$productDataNick])) {
            return [];
        }

        return (array)$this->productData[$productDataNick]['variation_attributes'];
    }

    //########################################

    private function load()
    {
        if ($this->marketplaceId === null) {
            throw new \Ess\M2ePro\Model\Exception('Marketplace was not set.');
        }

        $connRead = $this->resourceConnection->getConnection();
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_marketplace');

        $data = $connRead->select()
            ->from($table)
            ->where('marketplace_id = ?', (int)$this->marketplaceId)
            ->query()
            ->fetch();

        if ($data === false) {
            throw new \Ess\M2ePro\Model\Exception('Marketplace not found or not synchronized');
        }

        $this->productData = $this->getHelper('Data')->jsonDecode($data['product_data']);
    }

    //########################################
}
