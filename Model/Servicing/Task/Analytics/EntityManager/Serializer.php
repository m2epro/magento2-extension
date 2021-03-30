<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager;

use \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager as EntityManager;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager\Serializer
 */
class Serializer extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    public function serializeData(\Ess\M2ePro\Model\ActiveRecord\AbstractModel $item, EntityManager $manager)
    {
        return [
            'component' => $manager->getComponent(),
            'entity'    => $manager->getEntityType(),
            'id'        => $item->getId(),
            'data'      => $this->prepareEntityData($item, $manager)
        ];
    }

    //########################################

    protected function prepareEntityData(\Ess\M2ePro\Model\ActiveRecord\AbstractModel $item, EntityManager $manager)
    {
        $data = $item->getData();

        unset(
            $data['id'],
            $data['component_mode'],
            $data['server_hash'],
            $data[strtolower($manager->getEntityType()).'_id']
        );

        switch ($manager->getComponent() .'::'. $manager->getEntityType()) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK . '::Account':
                unset($data['server_hash'], $data['token']);
                break;

            case \Ess\M2ePro\Helper\Component\Amazon::NICK . '::Listing':
                unset($data['account_id'], $data['additional_data']);
                break;

            case \Ess\M2ePro\Helper\Component\Amazon::NICK . '::Template_SellingFormat':
                /**@var $item \Ess\M2ePro\Model\Template\SellingFormat */
                $data['business_discounts'] = $this->unsetDataInRelatedItems(
                    $item->getChildObject()->getBusinessDiscounts(),
                    'template_selling_format_id'
                );
                break;

            case \Ess\M2ePro\Helper\Component\Amazon::NICK . '::Template_Description':
                /**@var $item \Ess\M2ePro\Model\Template\Description */
                $data['specifics'] = $this->unsetDataInRelatedItems(
                    $item->getChildObject()->getSpecifics(),
                    'template_description_id'
                );
                break;

            // ---------------------------------------

            case \Ess\M2ePro\Helper\Component\Ebay::NICK . '::Account':
                unset(
                    $data['server_hash'],
                    $data['token_session'],
                    $data['sell_api_token_session'],
                    $data['info'],
                    $data['user_preferences'],
                    $data['job_token']
                );
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK . '::Listing':
                unset($data['account_id'], $data['additional_data'], $data['product_add_ids']);
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK . '::Ebay_Template_Payment':
                /**@var $item \Ess\M2ePro\Model\Ebay\Template\Payment */
                $data['services'] = $this->unsetDataInRelatedItems($item->getServices(), 'template_payment_id');
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK . '::Ebay_Template_Shipping':
                /**@var $item \Ess\M2ePro\Model\Ebay\Template\Shipping */
                if ($calculated = $item->getCalculatedShipping()) {
                    $data['calculated'] = $calculated->getData();
                }

                $data['services'] = $this->unsetDataInRelatedItems($item->getServices(), 'template_shipping_id');
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK . '::Template_Description':
                unset($data['watermark_image'], $data['description_template']);
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK . '::Ebay_Template_Category':
                /**@var $item \Ess\M2ePro\Model\Ebay\Template\Category */
                $data['specifics'] = $this->unsetDataInRelatedItems($item->getSpecifics(), 'template_category_id');
                break;

            // ---------------------------------------

            case \Ess\M2ePro\Helper\Component\Walmart::NICK . '::Account':
                unset($data['server_hash'], $data['client_id'], $data['client_secret'], $data['private_key']);
                break;

            case \Ess\M2ePro\Helper\Component\Walmart::NICK . '::Template_SellingFormat':
                /**@var $item \Ess\M2ePro\Model\Template\SellingFormat */
                $data['shipping_overrides'] = $this->unsetDataInRelatedItems(
                    $item->getChildObject()->getShippingOverrides(),
                    'template_selling_format_id'
                );
                $data['promotions'] = $this->unsetDataInRelatedItems(
                    $item->getChildObject()->getPromotions(),
                    'template_selling_format_id'
                );
                break;

            case \Ess\M2ePro\Helper\Component\Walmart::NICK . '::Template_Description':
                unset($data['description_template']);
                break;

            case \Ess\M2ePro\Helper\Component\Walmart::NICK . '::Template_Category':
                /**@var $item \Ess\M2ePro\Model\Walmart\Template\Category */
                $data['specifics'] = $this->unsetDataInRelatedItems($item->getSpecifics(), 'template_category_id');
                break;
        }

        return $this->getHelper('Data')->jsonEncode($data);
    }

    //########################################

    protected function unsetDataInRelatedItems(array $items, $dataKey)
    {
        return array_map(
            function ($el) use ($dataKey) {
                unset($el[$dataKey]);
                return $el;
            },
            $items
        );
    }

    //########################################
}
