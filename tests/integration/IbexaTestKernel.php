<?php

declare(strict_types=1);

namespace Wizhippo\Tests\Integration\Schedule;

use Ibexa\AdminUi\Menu\MenuItemFactory;
use Ibexa\ContentForms\Form\Processor\ContentFormProcessor;
use Ibexa\Contracts\AdminUi\Notification\TranslatableNotificationHandlerInterface;
use Ibexa\Contracts\Core\Test\IbexaTestKernel as BaseIbexaTestKernel;
use LogicException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;
use Wizhippo\ScheduledContentBundle\WizhippoScheduledContentBundle;

final class IbexaTestKernel extends BaseIbexaTestKernel
{
    public function getSchemaFiles(): iterable
    {
        yield from parent::getSchemaFiles();

        yield from [
            $this->locateResource('@WizhippoScheduledContentBundle/Resources/schema/legacy.yaml'),
        ];
    }

    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield from [
            new WizhippoScheduledContentBundle(),
        ];
    }

    protected static function getExposedServicesByClass(): iterable
    {
        yield from parent::getExposedServicesByClass();

        yield ContentScheduleService::class;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->setParameter('locale_fallback', 'en');

            self::createSyntheticService($container, MenuItemFactory::class);
            self::createSyntheticService($container, ContentFormProcessor::class);
            self::createSyntheticService($container, TranslatableNotificationHandlerInterface::class);

            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => __DIR__.'/Resources/routing.yaml',
                ],
            ]);
        });
    }

    /**
     * Creates synthetic services in container, allowing compilation of container when some services are missing.
     * Additionally, those services can be replaced with mock implementations at runtime, allowing integration testing.
     *
     * You can set them up in KernelTestCase by calling `self::getContainer()->set($id, $this->createMock($class));`
     *
     * @phpstan-param class-string $class
     */
    private static function createSyntheticService(ContainerBuilder $container, string $class, ?string $id = null): void
    {
        $id = $id ?? $class;
        if ($container->has($id)) {
            throw new LogicException(
                sprintf(
                    'Expected test kernel to not contain "%s" service. A real service should not be overwritten by a mock',
                    $id,
                )
            );
        }

        $definition = new Definition($class);
        $definition->setSynthetic(true);
        $container->setDefinition($id, $definition);
    }
}
