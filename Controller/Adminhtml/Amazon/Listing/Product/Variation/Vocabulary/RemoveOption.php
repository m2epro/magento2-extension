<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class RemoveOption extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $productOption = $this->getRequest()->getParam('product_option');
        $productOptionsGroup = $this->getRequest()->getParam('product_options_group');
        $channelAttr = $this->getRequest()->getParam('channel_attr');

        if (empty($productOption) || empty($productOptionsGroup) || empty($channelAttr)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productOptionsGroup)) {
            $productOptionsGroup = htmlspecialchars_decode(
                $productOptionsGroup,
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
            );
            $productOptionsGroup = \Ess\M2ePro\Helper\Json::decode($productOptionsGroup);
        }

        $this->vocabularyHelper->removeOptionFromLocalStorage($productOption, $productOptionsGroup, $channelAttr);

        $this->setJsonContent([
            'success' => true,
        ]);

        return $this->getResult();
    }
}
