<?php declare(strict_types=1);

namespace Melv\CustomerAccountChanges\Core\Framework\Event;

use Melv\CustomerAccountChanges\Core\Checkout\Customer\Event\CustomerChangedEmailEvent;
use Melv\CustomerAccountChanges\Core\Checkout\Customer\Event\CustomerChangedPasswordEvent;
use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Shopware\Core\Framework\Event\BusinessEventDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BusinessEventCollectorSubscriber implements EventSubscriberInterface
{
    private const EVENT_CLASSES = [
        CustomerChangedPasswordEvent::class,
        CustomerChangedEmailEvent::class
    ];

    public function __construct(
        private readonly BusinessEventCollector $businessEventCollector
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BusinessEventCollectorEvent::NAME => ['onAddEvents', 1000],
        ];
    }

    public function onAddEvents(BusinessEventCollectorEvent $event): void
    {
        $collection = $event->getCollection();

        foreach (self::EVENT_CLASSES as $class) {
            $definition = $this->businessEventCollector->define($class);
            if ($definition instanceof BusinessEventDefinition) {
                $collection->set($class, $definition);
            }
        }
    }
}