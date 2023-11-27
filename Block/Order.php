<?php
declare(strict_types=1);

namespace Jajuma\PotOrderStatus\Block;

use Jajuma\PowerToys\Block\PowerToys\Dashboard;
use Jajuma\PotOrderStatus\Helper\Config as HelperConfig;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory as ReportsCollectionFactory;

class Order extends Dashboard
{
    /**
     * @var HelperConfig
     */
    protected HelperConfig $helperConfig;

    /**
     * @var DateTimeFactory
     */
    protected DateTimeFactory $dateTimeFactory;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @var ReportsCollectionFactory
     */
    protected ReportsCollectionFactory $reportsCollectionFactory;

    /**
     * @param Context $context
     * @param HelperConfig $helperConfig
     * @param DateTimeFactory $dateTimeFactory
     * @param CollectionFactory $collectionFactory
     * @param ReportsCollectionFactory $reportsCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HelperConfig $helperConfig,
        DateTimeFactory $dateTimeFactory,
        CollectionFactory $collectionFactory,
        ReportsCollectionFactory $reportsCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperConfig = $helperConfig;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->reportsCollectionFactory = $reportsCollectionFactory;
    }

    /**
     * Is enable
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->helperConfig->isEnable();
    }

    /**
     * Get last x hours
     *
     * @return mixed
     */
    public function getLastXHours()
    {
        return $this->helperConfig->getLastXHours();
    }

    /**
     * Get number order
     *
     * @param string $status
     * @return int
     */
    public function getNumberOrder(string $status = ''): int
    {
        $lastHours = $this->getLastXHours();
        $currentDateTime = $this->dateTimeFactory->create()->gmtDate();
        $minusCurrentDateTime = Date('Y-m-d H:i:s', strtotime('-'. $lastHours .' hours', strtotime($currentDateTime)));
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['gteq' => $minusCurrentDateTime]);

        if (!$status) {
            return $collection->getSize();
        }

        $collection->addFieldToFilter('status', $status);
        return $collection->getSize();
    }

    /**
     * Get last order
     *
     * @return DataObject
     */
    public function getLastOrder(): DataObject
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'desc')
            ->getFirstItem();
    }

    /**
     * Get revenue
     *
     * @return string
     */
    public function getRevenue()
    {
        $lastHours = $this->getLastXHours();
        $period = $lastHours;

        $collection = $this->reportsCollectionFactory->create()->addCreateAtPeriodFilter($period)->calculateTotals();
        $totals = $collection->getFirstItem();

        return number_format(floatval($totals->getRevenue()), 2, '.', ',');
    }
}
