<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\ProductVariationVocabulary
 */
class ProductVariationVocabulary extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'product_variation_vocabulary';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $metadata = $this->helperFactory->getObject('Module_Product_Variation_Vocabulary')->getServerMetaData();
        !isset($metadata['version']) && $metadata['version'] = null;

        return ['metadata' => $metadata];
    }

    public function processResponseData(array $data)
    {
        $helper = $this->helperFactory->getObject('Module_Product_Variation_Vocabulary');

        if (isset($data['data']) && is_array($data['data'])) {
            $helper->setServerData($data['data']);
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $helper->setServerMetadata($data['metadata']);
        }
    }

    //########################################
}
