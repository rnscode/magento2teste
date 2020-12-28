<?php

namespace RNSCODE\Challenge\Observer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\RequestFactory;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use RNSCODE\Challenge\Helper\Config;

class OrderPlaceAfter implements ObserverInterface
{

    private $configHelper;
    private $logger;
    private $clientFactory;
    private $responseFactory;
    private $requestFactory;
    private $customerFactory;

    public function __construct(
        Config $configHelper,
        LoggerInterface $customLogger,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        RequestFactory $requestFactory,
        CustomerFactory $customerFactory
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $customLogger;
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->requestFactory = $requestFactory;
        $this->customerFactory = $customerFactory;
    }

    public function execute(Observer $observer)
    {
        try {
            if ($this->configHelper->isModuleEnabled()) {
                /** @var Order $order */
                $order = $observer->getEvent()->getOrder();

                $customer = $this->customerFactory->create()->load($order->getCustomerId());
                $response = $this->request(
                    (object)[
                        'customer'             => (object)[
                            'name'      => $customer->getName(),
                            'telephone' => $order->getShippingAddress()->getTelephone(),
                            'dob'       => $order->getCustomerDob(),
                        ],
                        'shipping_address'     => (object)$order->getShippingAddress()->getData(),
                        'items'                => array_map(
                            function ($item) {
                                return (object)$item->getData();
                            },
                            $order->getAllItems()
                        ),
                        'shipping_method'      => $order->getShippingMethod(),
                        'payment_method'       => $order->getPayment()->getMethod(),
                        'payment_installments' => '',
                        'subtotal'             => $order->getSubtotal(),
                        'shipping_amount'      => $order->getShippingAmount(),
                        'discount'             => $order->getDiscountAmount(),
                        'total'                => $order->getGrandTotal(),
                    ]
                );
                if (in_array($response->getStatusCode(), [200, 201])) {
                    $this->logger->info('Dados enviados com sucesso', ['status' => $response->getStatusCode()]);
                } else {
                    throw new \Exception("status {$response->getStatusCode()}, reason: {$response->getReasonPhrase()}");
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar dados', ['exception' => $e->getMessage()]);
        }
    }

    private function request($data): Response
    {
        try {
            $client = $this->clientFactory->create([]);
            $uri = $this->configHelper->getEndpoint();
            $apiKey = $this->configHelper->getAPIKey();
            $headers = [
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ];
            $request = $this->requestFactory->create(
                [
                    'method'  => 'POST',
                    'uri'     => $uri,
                    'headers' => $headers,
                    'body'    => json_encode($data),
                ]
            );
            $response = $client->send($request);
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create(
                [
                    'status' => $exception->getCode(),
                    'reason' => $exception->getMessage(),
                ]
            );
        }
        return $response;
    }
}
