<?php

namespace Aligent\Webhooks\Service\Webhook;

use Aligent\Webhooks\Model\Webhook;
use GuzzleHttp\Client;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;

class NotifierFactory implements NotifierFactoryInterface
{
    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    public function __construct(Client $client, Json $json, EncryptorInterface $encryptor)
    {
        $this->client = $client;
        $this->json = $json;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Webhook $webhook, string $objectData): NotifierInterface
    {
        // TODO: subscription_id as switch case is just a placeholder for now, actual implementation must use a relevant
        // field
        switch ($webhook->getSubscriptionId()) {
            default:
                return new HttpNotifier(
                    $webhook->getSubscriptionId(),
                    $objectData,
                    $webhook->getRecipientUrl(),
                    $webhook->getVerificationToken(),
                    $this->client,
                    $this->json,
                    $this->encryptor
                );
//            default:
//                use a default fallback notifier or throw an exception
//                return new ExampleNotifier($objectData);
        }
    }
}
