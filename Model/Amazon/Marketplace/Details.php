<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Marketplace;

class Details extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var int */
    private $marketplaceId = null;
    /** @var array  */
    private $productTypes = [];
    /** @var \Magento\Framework\App\ResourceConnection  */
    private $resourceConnection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory);
    }

    /**
     * @param $marketplaceId
     *
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

    /**
     * @return array
     */
    public function getProductTypes(): array
    {
        return $this->productTypes;
    }

    /**
     * @param string $productTypeNick
     *
     * @return array
     */
    public function getVariationThemes(string $productTypeNick): array
    {
        return !empty($this->productTypes[$productTypeNick]['variation_themes']) ?
            $this->productTypes[$productTypeNick]['variation_themes'] : [];
    }

    /**
     * @param string $productTypeNick
     * @param string $theme
     *
     * @return array
     */
    public function getVariationThemeAttributes(string $productTypeNick, string $theme): array
    {
        $themes = $this->getVariationThemes($productTypeNick);

        return !empty($themes[$theme]['attributes']) ? $themes[$theme]['attributes'] : [];
    }

    private function load()
    {
        if ($this->marketplaceId === null) {
            throw new \Ess\M2ePro\Model\Exception('Marketplace was not set.');
        }

        $connRead = $this->resourceConnection->getConnection();
        $table = $this->getHelper('Module_Database_Structure')
                      ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

        $data = $connRead->select()
                         ->from($table)
                         ->where('marketplace_id = ?', (int)$this->marketplaceId)
                         ->query()
                         ->fetch();

        if ($data === false) {
            throw new \Ess\M2ePro\Model\Exception('Marketplace not found or not synchronized');
        }

        $this->productTypes = \Ess\M2ePro\Helper\Json::decode($data['product_types']);
    }
}
