<?php

namespace Aligent\Webhooks\Service\Webhook;

use Aligent\Webhooks\Api\WebhookRepositoryInterface;
use Aligent\Webhooks\Helper\NotifierResult;
use Aligent\Webhooks\Helper\QueueMetadataInterface;
use Aligent\Webhooks\Model\Webhook;
use Aligent\Webhooks\Model\WebhookLogFactory;
use Aligent\Webhooks\Model\WebhookLogRepository;
use Magento\Framework\Amqp\ConfigPool;
use Magento\Framework\Amqp\Topology\BindingInstallerInterface;
use Magento\Framework\Amqp\Topology\QueueInstaller;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\MessageQueue\Publisher;
use Magento\Framework\MessageQueue\Topology\Config\ExchangeConfigItem\BindingFactory;
use Magento\Framework\MessageQueue\Topology\Config\QueueConfigItemFactory;

class EventDispatcher
{
    /**
     * @var WebhookRepositoryInterface
     */
    private WebhookRepositoryInterface $webhookRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var NotifierFactoryInterface
     */
    private NotifierFactoryInterface $notifierFactory;

    /**
     * @var WebhookLogRepository
     */
    private WebhookLogRepository $webhookLogRepository;

    /**
     * @var WebhookLogFactory
     */
    private WebhookLogFactory $webhookLogFactory;

    /**
     * @var RetryManager
     */
    private RetryManager $retryManager;

    /**
     * @param WebhookRepositoryInterface $webhookRepository
     * @param WebhookLogRepository $webhookLogRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param NotifierFactoryInterface $notifierFactory
     * @param WebhookLogFactory $webhookLogFactory
     * @param RetryManager $retryManager
     */
    public function __construct(
        WebhookRepositoryInterface $webhookRepository,
        WebhookLogRepository $webhookLogRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        NotifierFactoryInterface $notifierFactory,
        WebhookLogFactory $webhookLogFactory,
        RetryManager $retryManager
    ) {
        $this->webhookRepository = $webhookRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifierFactory = $notifierFactory;
        $this->webhookLogRepository = $webhookLogRepository;
        $this->webhookLogFactory = $webhookLogFactory;
        $this->retryManager = $retryManager;
    }

    /**
     * @param string $eventName
     * @param mixed $output
     */
    public function dispatch(string $eventName, $output)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', 1)
            ->addFilter('event_name', $eventName)
            ->create();

        $webhooks = $this->webhookRepository->getList($searchCriteria)->getItems();

        /** @var Webhook $webhook */
        foreach ($webhooks as $webhook) {
            $handler = $webhook->getMetadata();

            $notifier = $this->notifierFactory->create($handler);

            $response = $notifier->notify($webhook, [
                'data' => $output
            ]);

            if (!$response->getSuccess()) {
                $this->retryManager->place();
            }

            $this->log($response);
        }
    }

    /**
     * @param NotifierResult $response
     */
    private function log(NotifierResult $response): void
    {
        $webhookLog = $this->webhookLogFactory->create();
        $webhookLog->setSuccess($response->getSuccess());
        $webhookLog->setSubscriptionId($response->getSubscriptionId());
        $webhookLog->setResponseData($response->getResponseData());

        try {
            $this->webhookLogRepository->save($webhookLog);
        } catch (AlreadyExistsException $exception) {
            // Do nothing because a log entry can never already exist
        }
    }

}
