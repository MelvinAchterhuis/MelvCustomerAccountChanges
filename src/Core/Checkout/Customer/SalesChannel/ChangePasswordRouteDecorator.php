<?php declare(strict_types=1);

namespace Melv\CustomerAccountChanges\Core\Checkout\Customer\SalesChannel;

use Melv\CustomerAccountChanges\Core\Checkout\Customer\Event\CustomerChangedPasswordEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractChangePasswordRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class ChangePasswordRouteDecorator extends AbstractChangePasswordRoute
{
    public function __construct(
        private readonly AbstractChangePasswordRoute $decorated,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractChangePasswordRoute
    {
        return $this->decorated;
    }

    public function change(RequestDataBag $requestDataBag, SalesChannelContext $context, CustomerEntity $customer): ContextTokenResponse
    {
        $response = $this->decorated->change($requestDataBag, $context, $customer);

        $event = new CustomerChangedPasswordEvent($context, $customer);
        $this->eventDispatcher->dispatch($event);

        return $response;
    }
}