<?php

namespace Aligent\Webhooks\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{

    public function convert($source)
    {
        $output = [];
        /** @var \DOMNodeList $webhooks */
        $webhooks = $source->getElementsByTagName('webhook');

        /** @var \DOMNode $webhookConfig */
        foreach ($webhooks as $webhookConfig) {
            $hookName = $webhookConfig->attributes->getNamedItem('hook_name')->nodeValue;

            $webhookService = [];

            /** @var \DOMNode $serviceConfig */
            foreach ($webhookConfig->childNodes as $serviceConfig) {
                if ($serviceConfig->nodeName != 'service' || $serviceConfig->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                $webhookServiceNameNode = $serviceConfig->attributes->getNamedItem('class');
                if (!$webhookServiceNameNode) {
                    throw new \InvalidArgumentException('Attribute class is missing');
                }

                $webhookService = $this->__convertServiceConfig($serviceConfig);
            }
            $output[mb_strtolower($hookName)] = $webhookService;
        }

        return $output;
    }

    public function __convertServiceConfig($observerConfig)
    {
        $output = [];
        /** Parse class configuration */
        $classAttribute = $observerConfig->attributes->getNamedItem('class');
        if ($classAttribute) {
            $output['class'] = $classAttribute->nodeValue;
        }

        /** Parse instance method configuration */
        $methodAttribute = $observerConfig->attributes->getNamedItem('method');
        if ($methodAttribute) {
            $output['method'] = $methodAttribute->nodeValue;
        }

        return $output;
    }
}