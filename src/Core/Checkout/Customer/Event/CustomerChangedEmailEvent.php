<?php declare(strict_types=1);

namespace Melv\CustomerAccountChanges\Core\Checkout\Customer\Event;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\CustomerAware;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CustomerChangedEmailEvent extends Event implements SalesChannelAware, ShopwareSalesChannelEvent, CustomerAware, MailAware, FlowEventAware, ScalarValuesAware
{
    final public const EVENT_NAME = 'checkout.customer.changed-email';

    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private readonly CustomerEntity $customer,
        private readonly string $oldEmail,
        private readonly string $newEmail
    ) {
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannel()->getId();
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('oldEmail', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('newEmail', new ScalarValueType(ScalarValueType::TYPE_STRING))
        ;
    }

    /**
     * @return array<string, scalar|array<mixed>|null>
     */
    public function getValues(): array
    {
        return [
            'oldEmail' => $this->oldEmail,
            'newEmail' => $this->newEmail
        ];
    }

    public function getCustomerId(): string
    {
        return $this->getCustomer()->getId();
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return new MailRecipientStruct(
            [
                $this->customer->getEmail() => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
            ]
        );
    }

    public function getOldEmail(): string
    {
        return $this->oldEmail;
    }

    public function getNewEmail(): string
    {
        return $this->newEmail;
    }
}