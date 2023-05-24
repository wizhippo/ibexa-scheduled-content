<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle;

use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Wizhippo\ScheduledContentBundle\DependencyInjection\Security\PolicyProvider\ContentSchedulePolicyProvider;

final class WizhippoScheduledContentBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        /** @var IbexaCoreExtension $ibexaCoreExtension */
        $ibexaCoreExtension = $container->getExtension('ibexa');
        $ibexaCoreExtension->addPolicyProvider(new ContentSchedulePolicyProvider());
    }
}
