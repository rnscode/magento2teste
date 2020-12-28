<?php

namespace RNSCODE\Challenge\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use RNSCODE\Challenge\Helper\Config;

class OrderPlaceAfter implements ObserverInterface
{

    private $configHelper;
    private $logger;

    public function __construct(
        Config $configHelper,
        LoggerInterface $customLogger
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $customLogger;
    }

    public function execute(Observer $observer)
    {
        try {
            if ($this->configHelper->isModuleEnabled()) {
                $order = $observer->getEvent()->getOrder();
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar dados', ['exception' => $e]);
        }
        return $this;
    }
}
