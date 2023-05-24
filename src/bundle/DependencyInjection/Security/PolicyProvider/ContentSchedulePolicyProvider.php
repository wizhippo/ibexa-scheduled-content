<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\DependencyInjection\Security\PolicyProvider;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

final class ContentSchedulePolicyProvider extends YamlPolicyProvider
{
    public function getFiles(): array
    {
        return [
            __DIR__.'/../../../Resources/config/policies.yaml',
        ];
    }
}
