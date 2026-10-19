<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class ResetHangingUploads extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/resetHangingUploads';

    /** @var int 1 day in seconds */
    private const HANGING_TIMEOUT_SECONDS = 86400;
    private const PROCESSING_BATCH_SIZE = 500;

    /** @var int 8 hours in seconds */
    protected $interval = 8 * 60 * 60;

    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository;
    private \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository,
        \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->complianceDocumentsRepository = $complianceDocumentsRepository;
        $this->videoRepository = $videoRepository;
    }

    protected function performActions(): void
    {
        $boundaryDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify(
            sprintf('-%d seconds', self::HANGING_TIMEOUT_SECONDS)
        );
        $this->resetComplianceDocuments($boundaryDate, self::PROCESSING_BATCH_SIZE);
        $this->resetVideos($boundaryDate, self::PROCESSING_BATCH_SIZE);
    }

    private function resetComplianceDocuments(\DateTime $boundaryDate, int $limit): void
    {
        $documents = $this->complianceDocumentsRepository
            ->getHangingRecords($boundaryDate, $limit);
        foreach ($documents as $document) {
            $document->setStatusPending();
            $this->complianceDocumentsRepository->update($document);
        }
    }

    private function resetVideos(\DateTime $boundaryDate, int $limit): void
    {
        $videos = $this->videoRepository
            ->getHangingRecords($boundaryDate, $limit);
        foreach ($videos as $video) {
            $video->setStatusPending();
            $this->videoRepository->save($video);
        }
    }
}
