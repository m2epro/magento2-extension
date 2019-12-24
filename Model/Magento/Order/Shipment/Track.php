<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;

/**
 * Class \Ess\M2ePro\Model\Magento\Order\Shipment\Track
 */
class Track extends \Ess\M2ePro\Model\AbstractModel
{
    protected $shipmentTrackFactory;
    /** @var $shipment \Magento\Sales\Model\Order */
    protected $magentoOrder = null;

    protected $supportedCarriers = [];

    protected $trackingDetails = [];

    protected $tracks = [];

    //########################################

    public function __construct(
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @return $this
     */
    public function setMagentoOrder(\Magento\Sales\Model\Order $magentoOrder)
    {
        $this->magentoOrder = $magentoOrder;
        return $this;
    }

    //########################################

    /**
     * @param array $trackingDetails
     * @return $this
     */
    public function setTrackingDetails(array $trackingDetails)
    {
        $this->trackingDetails = $trackingDetails;
        return $this;
    }

    //########################################

    /**
     * @param array $supportedCarriers
     * @return $this
     */
    public function setSupportedCarriers(array $supportedCarriers)
    {
        $this->supportedCarriers = $supportedCarriers;
        return $this;
    }

    //########################################

    public function getTracks()
    {
        return $this->tracks;
    }

    //########################################

    public function buildTracks()
    {
        $this->prepareTracks();
    }

    //########################################

    private function prepareTracks()
    {
        $trackingDetails = $this->getFilteredTrackingDetails();
        if (count($trackingDetails) == 0) {
            return null;
        }

        // Skip shipment observer
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
        $this->getHelper('Data\GlobalData')->setValue('skip_shipment_observer', true);
        // ---------------------------------------

        /** @var $shipment \Magento\Sales\Model\Order\Shipment */
        $shipment = $this->magentoOrder->getShipmentsCollection()->getFirstItem();

        foreach ($trackingDetails as $trackingDetail) {
            /** @var $track \Magento\Sales\Model\Order\Shipment\Track */
            $track = $this->shipmentTrackFactory->create();
            $track->setNumber($trackingDetail['number']);
            $track->setTitle($trackingDetail['title']);
            $track->setCarrierCode($this->getCarrierCode($trackingDetail['title']));

            $shipment->addTrack($track)->save();
            $this->tracks[] = $track;
        }
    }

    // ---------------------------------------

    private function getFilteredTrackingDetails()
    {
        if ($this->magentoOrder->getTracksCollection()->getSize() <= 0) {
            return $this->trackingDetails;
        }

        // Filter exist tracks
        // ---------------------------------------
        foreach ($this->magentoOrder->getTracksCollection() as $track) {
            foreach ($this->trackingDetails as $key => $trackingDetail) {
                if ($track->getData('track_number') == $trackingDetail['number']) {
                    unset($this->trackingDetails[$key]);
                }
            }
        }
        // ---------------------------------------

        return $this->trackingDetails;
    }

    // ---------------------------------------

    private function getCarrierCode($title)
    {
        $carrierCode = strtolower($title);

        return isset($this->supportedCarriers[$carrierCode]) ? $carrierCode : 'custom';
    }

    //########################################
}
