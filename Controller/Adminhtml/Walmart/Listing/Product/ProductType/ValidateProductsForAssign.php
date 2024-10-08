<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\ProductType;

class ValidateProductsForAssign extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $messages = [];

        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (\count($productsIds) != \count($productsIdsLocked)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Product Type cannot be assigned because the Products are in Action.'
                ),
            ];
        }

        if (empty($productsIdsLocked)) {
            $this->setJsonContent([
                'messages' => $messages,
            ]);

            return $this->getResult();
        }

        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\ProductType::class);
        if (!empty($messages)) {
            $block->setMessages($messages);
        }

        $this->setJsonContent([
            'html' => $block->toHtml(),
            'messages' => $messages,
            'products_ids' => implode(',', $productsIdsLocked),
        ]);

        return $this->getResult();
    }

    private function filterLockedProducts($productsIdsParam): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable();

        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $select = $connection->select();
            $select->from(['lo' => $table], ['object_id'])
                   ->where('model_name = "Listing_Product"')
                   ->where('object_id IN (?)', $productsIdsParamChunk)
                   ->where('tag IS NOT NULL');

            $lockedProducts = $connection->fetchCol($select);

            foreach ($lockedProducts as $id) {
                $key = array_search($id, $productsIdsParamChunk);
                if ($key !== false) {
                    unset($productsIdsParamChunk[$key]);
                }
            }

            $productsIds = array_merge($productsIds, $productsIdsParamChunk);
        }

        return $productsIds;
    }
}
