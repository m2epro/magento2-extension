<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class AddOptions extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Walmart\Vocabulary */
    private $vocabularyHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->vocabularyHelper = $vocabularyHelper;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $optionsData          = $this->getRequest()->getParam('options_data');
        $isRememberAutoAction = (bool)$this->getRequest()->getParam('is_remember', false);
        $needAddToVocabulary  = (bool)$this->getRequest()->getParam('need_add', false);

        if (!empty($optionsData)) {
            $optionsData = $this->dataHelper->jsonDecode($optionsData);
        }

        if (!$isRememberAutoAction && !$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction && !$needAddToVocabulary) {
            $this->vocabularyHelper->disableOptionAutoAction();
            return;
        }

        if (!$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction) {
            $this->vocabularyHelper->enableOptionAutoAction();
        }

        if (empty($optionsData)) {
            return;
        }

        foreach ($optionsData as $channelAttribute => $options) {
            foreach ($options as $productOption => $channelOption) {
                $this->vocabularyHelper->addOption($productOption, $channelOption, $channelAttribute);
            }
        }
    }
}
