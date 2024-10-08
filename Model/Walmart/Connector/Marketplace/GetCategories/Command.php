<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories;

class Command extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    public const PARAM_KEY_MARKETPLACE_ID = 'marketplace_id';
    public const PARAM_KEY_PART_NUMBER = 'part_number';

    protected function getCommand()
    {
        return ['marketplace', 'get', 'categories'];
    }

    protected function getRequestData(): array
    {
        return [
            'marketplace' => $this->params[self::PARAM_KEY_MARKETPLACE_ID],
            'part_number' => $this->params[self::PARAM_KEY_PART_NUMBER],
        ];
    }

    protected function prepareResponseData()
    {
        $categories = [];

        $response = $this->getResponse()->getResponseData();
        foreach ($response['categories'] as $responseCategory) {
            $category = new Response\Category(
                $responseCategory['id'],
                $responseCategory['parent_id'],
                $responseCategory['title'],
                $responseCategory['is_leaf']
            );

            if ($responseCategory['is_leaf']) {
                $category->setProductType(
                    new Response\Category\ProductType(
                        $responseCategory['product_type']['title'],
                        $responseCategory['product_type']['nick']
                    )
                );
            }

            $categories[] = $category;
        }

        $part = new Response\Part(
            $response['total_parts'],
            $response['next_part']
        );
        $this->responseData = new Response($categories, $part);
    }

    protected function validateResponse(): bool
    {
        $response = $this->getResponse()->getResponseData();

        return isset($response['categories'])
            && isset($response['total_parts'])
            && array_key_exists('next_part', $response);
    }
}
