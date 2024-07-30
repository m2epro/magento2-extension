<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Save extends Settings
{
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Manager $saveService;
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler $changeHandler;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler $changeHandler,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Manager $saveService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->saveService = $saveService;
        $this->changeHandler = $changeHandler;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post || empty($post['mapping'])) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }

        $changedTitles = [];
        foreach ($post['mapping'] as $base64EncodedTitle => $attributeCode) {
            $title = base64_decode($base64EncodedTitle);
            $mapping = $this->saveService->save($title, $attributeCode);

            if ($mapping !== null) {
                $changedTitles[] = $mapping->getTitle();
            }
        }

        $this->changeHandler->handle($changedTitles);

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
