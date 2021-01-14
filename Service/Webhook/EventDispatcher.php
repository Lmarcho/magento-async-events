<?php

namespace Aligent\Webhooks\Service\Webhook;

use Aligent\Webhooks\Api\WebhookRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class EventDispatcher
{
    /**
     * A collection of listeners listening to this event
     * @var HttpNotifier[]
     */
    private array $subscribers = [];

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

    public function __construct(
        WebhookRepositoryInterface $webhookRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        NotifierFactoryInterface $notifierFactory
    ) {
        $this->webhookRepository = $webhookRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->notifierFactory = $notifierFactory;
    }

    public function loadSubscribers(string $eventName, string $objectId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', 1)
            ->addFilter('event_name', $eventName)
            ->create();

        $webhooks = $this->webhookRepository->getList($searchCriteria)->getItems();

        $subscribers = [];

        /** @var \Aligent\Webhooks\Model\Webhook $webhook */
        foreach ($webhooks as $webhook) {
            $subscribers[] = $this->notifierFactory->create($webhook, $objectId);
        }

        return $subscribers;
    }

    public function dispatch(array $subscribers)
    {
        foreach ($subscribers as $subscriber) {
            $subscriber->notify();
        }
    }
}
