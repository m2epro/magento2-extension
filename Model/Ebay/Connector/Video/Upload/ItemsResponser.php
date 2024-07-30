<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Video\Upload;

class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    private \Ess\M2ePro\Model\Ebay\Video\UploadingStatusProcessor $videoUpdating;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;
    protected $params = [];

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\UploadingStatusProcessor $videoUpdating,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory
        );

        $this->videoUpdating = $videoUpdating;
        $this->exceptionHelper = $exceptionHelper;
        $this->params = $params;
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['videos'])) {
            return false;
        }

        return true;
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['videos'] as $video) {
            if ($video['is_success']) {
                $channelVideo = \Ess\M2ePro\Model\Ebay\Video\Channel\Video::createUploaded(
                    $video['url'],
                    $video['ebay_video_id']
                );
            } else {
                $channelVideo = \Ess\M2ePro\Model\Ebay\Video\Channel\Video::createNotUploaded(
                    $video['url'],
                    $video['error']
                );
            }

            $this->preparedResponseData[] = $channelVideo;
        }
    }

    protected function isNeedProcessResponse(): bool
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    protected function processResponseData()
    {
        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->ebayFactory->getObjectLoaded('Account', $this->params['account_id']);

            $this->videoUpdating->processResponseData($account, $this->getPreparedResponseData());
        } catch (\Throwable $e) {
            $this->exceptionHelper->process($e);
        }
    }
}
