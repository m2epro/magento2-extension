<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class AddAttributes extends Main
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

        $this->dataHelper = $dataHelper;
        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $attributes           = $this->getRequest()->getParam('attributes');
        $isRememberAutoAction = (bool)$this->getRequest()->getParam('is_remember', false);
        $needAddToVocabulary  = (bool)$this->getRequest()->getParam('need_add', false);

        if (!empty($attributes)) {
            $attributes = $this->dataHelper->jsonDecode($attributes);
        }

        if (!$isRememberAutoAction && !$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction && !$needAddToVocabulary) {
            $this->vocabularyHelper->disableAttributeAutoAction();
            return;
        }

        if (!$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction) {
            $this->vocabularyHelper->enableAttributeAutoAction();
        }

        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $productAttribute => $channelAttribute) {
            $this->vocabularyHelper->addAttribute($productAttribute, $channelAttribute);
        }
    }
}
