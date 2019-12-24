<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Search\ByQuery;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Search\ByQuery\ItemsResponser
 */
abstract class ItemsResponser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
{
    // ########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['items']) && !isset($responseData['unavailable'])) {
            return false;
        }

        return true;
    }

    // ########################################

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();

        if (!empty($responseData['unavailable'])) {
            $this->preparedResponseData = false;
            return;
        }

        $result = [];

        foreach ($responseData['items'] as $item) {
            $product = [
                'general_id' => $item['product_id'],
                'brand' => isset($item['brand']) ? $item['brand'] : '',
                'title' => $item['title'],
                'image_url' => $item['image_url'],
                'is_variation_product' => $item['is_variation_product'],
            ];

            if ($product['is_variation_product']) {
                if (empty($item['bad_parent'])) {
                    $product += [
                        'parentage' => $item['parentage'],
                        'variations' => $item['variations'],
                        'bad_parent' => false
                    ];
                } else {
                    $product['bad_parent'] = (bool)$item['bad_parent'];
                }
            }

            if (!empty($item['list_price'])) {
                $product['list_price'] = [
                    'amount' => $item['list_price']['amount'],
                    'currency' => $item['list_price']['currency'],
                ];
            }

            if (!empty($item['requested_child_id'])) {
                $product['requested_child_id'] = $item['requested_child_id'];
            }

            $result[] = $product;
        }

        $this->preparedResponseData = $result;
    }

    // ########################################
}
