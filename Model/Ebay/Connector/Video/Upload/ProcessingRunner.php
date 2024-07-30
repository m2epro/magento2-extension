<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Video\Upload;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single\Runner
{
    private \Ess\M2ePro\Model\Ebay\Video\Repository $repository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\Repository $repository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->repository = $repository;

        parent::__construct($parentFactory, $activeRecordFactory, $helperData, $helperFactory, $modelFactory);
    }

    public function processExpired(): void
    {
        $this->rollbackVideoStatus();

        parent::processExpired();
    }

    public function complete(): void
    {
        if ($this->isEmptyResponseOrHasError()) {
            $this->rollbackVideoStatus();
        }

        parent::complete();
    }

    private function isEmptyResponseOrHasError(): bool
    {
        if (
            empty($this->processingObject->getResultData())
            || $this->getResponse()->getMessages()->hasErrorEntities()
        ) {
            return true;
        }

        return false;
    }

    private function rollbackVideoStatus(): void
    {
        $responserParams = $this->getResponserParams();
        $videoUrls = $responserParams['videos'];
        $accountId = (int)$responserParams['account_id'];

        foreach ($videoUrls as $videoUrl) {
            $video = $this->repository->findByAccountIdAndUrl($accountId, $videoUrl);

            if ($video === null || !$video->isStatusUploading()) {
                continue;
            }

            $video->setStatusPending();
            $video->save();
        }
    }
}
