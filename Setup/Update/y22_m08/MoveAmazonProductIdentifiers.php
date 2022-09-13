<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class MoveAmazonProductIdentifiers extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $this->moveProductIdentifiersFromTemplate();
        $this->moveProductIdentifiersFromListing();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Statement_Exception
     */
    private function moveProductIdentifiersFromTemplate(): void
    {
        $configs = [
            'worldwide_id_mode'             => 0,
            'worldwide_id_custom_attribute' => null,
            'product_id_override_mode'      => 0,
        ];

        $templateDescriptionId = $this->getIdOfMostPopularTemplateDescription();
        $descriptionTableModifier = $this->getTableModifier('amazon_template_description');

        if (
            $templateDescriptionId !== null
            && $descriptionTableModifier->isColumnExists('worldwide_id_mode')
            && $descriptionTableModifier->isColumnExists('worldwide_id_custom_attribute')
        ) {
            $query = $this->getConnection()
                          ->select()
                          ->from(
                              $this->getFullTableName('amazon_template_description'),
                              ['template_description_id', 'worldwide_id_mode', 'worldwide_id_custom_attribute']
                          )
                          ->where('template_description_id = ?', $templateDescriptionId)
                          ->query();

            $row = $query->fetch();

            if (isset($row['worldwide_id_mode'])) {
                $configs['worldwide_id_mode'] = $row['worldwide_id_mode'];
            }

            if (isset($row['worldwide_id_custom_attribute'])) {
                $configs['worldwide_id_custom_attribute'] = $row['worldwide_id_custom_attribute'];
            }
        }

        $this->getConfigModifier()->insert(
            '/amazon/configuration/',
            'worldwide_id_mode',
            $configs['worldwide_id_mode']
        );

        $this->getConfigModifier()->insert(
            '/amazon/configuration/',
            'worldwide_id_custom_attribute',
            $configs['worldwide_id_custom_attribute']
        );

        $this->getConfigModifier()->insert(
            '/amazon/configuration/',
            'product_id_override_mode',
            $configs['product_id_override_mode']
        );

        $descriptionTableModifier->dropColumn('worldwide_id_mode');
        $descriptionTableModifier->dropColumn('worldwide_id_custom_attribute');
        $descriptionTableModifier->dropColumn('registered_parameter');
    }

    /**
     * @return int|null
     * @throws \Zend_Db_Statement_Exception
     */
    private function getIdOfMostPopularTemplateDescription(): ?int
    {
        $query = $this->getConnection()
                      ->select()
                      ->from(
                          $this->getFullTableName('amazon_listing_product'),
                          ['template_description_id', 'COUNT(*) AS count']
                      )
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

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Statement_Exception
     */
    private function moveProductIdentifiersFromListing(): void
    {
        $configs = [
            'general_id_mode'             => 0,
            'general_id_custom_attribute' => null,
        ];

        $listingId = $this->getIdOfMostPopularListing();
        $amazonListingTableModifier = $this->getTableModifier('amazon_listing');

        if (
            $listingId !== null
            && $amazonListingTableModifier->isColumnExists('general_id_mode')
            && $amazonListingTableModifier->isColumnExists('general_id_custom_attribute')
        ) {
            $query = $this->getConnection()
                          ->select()
                          ->from(
                              $this->getFullTableName('amazon_listing'),
                              ['listing_id', 'general_id_mode', 'general_id_custom_attribute']
                          )
                          ->where('listing_id = ?', $listingId)
                          ->query();

            $row = $query->fetch();

            if (isset($row['general_id_mode'])) {
                $configs['general_id_mode'] = $row['general_id_mode'];
            }

            if (isset($row['general_id_custom_attribute'])) {
                $configs['general_id_custom_attribute'] = $row['general_id_custom_attribute'];
            }
        }

        $this->getConfigModifier()->insert(
            '/amazon/configuration/',
            'general_id_mode',
            $configs['general_id_mode']
        );

        $this->getConfigModifier()->insert(
            '/amazon/configuration/',
            'general_id_custom_attribute',
            $configs['general_id_custom_attribute']
        );

        $amazonListingTableModifier->dropColumn('general_id_mode');
        $amazonListingTableModifier->dropColumn('general_id_custom_attribute');
        $amazonListingTableModifier->dropColumn('worldwide_id_mode');
        $amazonListingTableModifier->dropColumn('worldwide_id_custom_attribute');
        $amazonListingTableModifier->dropColumn('search_by_magento_title_mode');
    }

    /**
     * @return int|null
     * @throws \Zend_Db_Statement_Exception
     */
    private function getIdOfMostPopularListing(): ?int
    {
        $query = $this->getConnection()
                      ->select()
                      ->from(
                          $this->getFullTableName('listing_product'),
                          ['listing_id', 'COUNT(*) AS count']
                      )
                      ->where('component_mode = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK)
                      ->group('listing_id')
                      ->order('count DESC')
                      ->query();

        /** @var array $row */
        $row = $query->fetch();

        if (!isset($row['listing_id'])) {
            return null;
        }

        return (int)$row['listing_id'];
    }
}
