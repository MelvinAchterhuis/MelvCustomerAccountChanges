<?php declare(strict_types=1);

namespace Melv\CustomerAccountChanges\Core\Checkout\Customer\SalesChannel;

use Melv\CustomerAccountChanges\Core\Checkout\Customer\Event\CustomerChangedEmailEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractChangeEmailRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class ChangeEmailRouteDecorator extends AbstractChangeEmailRoute
{
    public function __construct(
        private readonly AbstractChangeEmailRoute $decorated,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractChangeEmailRoute
    {
        return $this->decorated;
    }

    public function change(RequestDataBag $requestDataBag, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        $response = $this->decorated->change($requestDataBag, $context, $customer);
        $oldEmail = $customer->getEmail();
        $newEmail = $requestDataBag->get('email');

        $event = new CustomerChangedEmailEvent($context, $customer, $oldEmail, $newEmail);
        $this->eventDispatcher->dispatch($event);

        return $response;
    }
}