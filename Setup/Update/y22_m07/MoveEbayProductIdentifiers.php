<?php

namespace Ess\M2ePro\Setup\Update\y22_m07;

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
*/

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class MoveEbayProductIdentifiers extends AbstractFeature
{
    private $identifiers = ['upc', 'ean', 'isbn', 'epid'];

    public function execute()
    {
        $configs = $this->getConfigValues();

        foreach ($this->identifiers as $identifier) {
            $this->getConfigModifier()->insert(
                '/ebay/configuration/',
                $identifier . '_mode',
                $configs[$identifier . '_mode']
            );

            $this->getConfigModifier()->insert(
                '/ebay/configuration/',
                $identifier . '_custom_attribute',
                $configs[$identifier . '_custom_attribute']
            );
        }

        $templateDescriptionTable = $this->getFullTableName('ebay_template_description');
        $query = $this->getConnection()->select()->from($templateDescriptionTable)->query();

        while ($row = $query->fetch()) {
            if (isset($row['product_details'])) {
                $productDetails = json_decode($row['product_details'], true);
                unset($productDetails['upc']);
                unset($productDetails['ean']);
                unset($productDetails['isbn']);
                unset($productDetails['epid']);
                $productDetails = json_encode($productDetails);

                $this->getConnection()->update(
                    $templateDescriptionTable,
                    ['product_details'             => $productDetails],
                    ['template_description_id = ?' => (int)$row['template_description_id']]
                );
            }
        }
    }

    private function getConfigValues()
    {
        $configs = [
            'upc_mode'              => 0,
            'upc_custom_attribute'  => null,
            'ean_mode'              => 0,
            'ean_custom_attribute'  => null,
            'isbn_mode'             => 0,
            'isbn_custom_attribute' => null,
            'epid_mode'             => 0,
            'epid_custom_attribute' => null
        ];

        $templateDescriptionId = $this->getIdOfMostPopularTemplateDescription();

        if ($templateDescriptionId === null) {
            return $configs;
        }

        $ebayTemplateDescriptionTable = $this->getFullTableName('ebay_template_description');
        $query = $this->getConnection()
                      ->select()
                      ->from($ebayTemplateDescriptionTable, ['template_description_id', 'product_details'])
                      ->where('template_description_id = ?', $templateDescriptionId)
                      ->query();

        $row = $query->fetch();

        $productDetails = json_decode($row["product_details"], true);

        if (empty($row["product_details"])) {
            return $configs;
        }

        foreach ($this->identifiers as $identifier) {
            if (isset($productDetails[$identifier]['mode'])) {
                $configs[$identifier . '_mode'] = $productDetails[$identifier]['mode'];
            }

            if (isset($productDetails[$identifier]['attribute'])) {
                $configs[$identifier . '_custom_attribute'] = $productDetails[$identifier]['attribute'];
            }
        }

        return $configs;
    }

    private function getIdOfMostPopularTemplateDescription(): ?int
    {
        $tableName = $this->getFullTableName('ebay_listing');
        $query = $this->getConnection()
                      ->select()
                      ->from($tableName, ['template_description_id', 'COUNT(*) AS count'])
                      ->group('template_description_id')
                      ->order('count DESC')
                      ->query();

        /** @var array $row */
        $row = $query->fetch();

        if (!isset($row['template_description_id'])) {
            return null;
        }

        return (int)$row['template_description_id'];
    }
}
