<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\EventListener;

use Ibexa\AdminUi\Menu\ContentEditRightSidebarBuilder;
use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Ibexa\AdminUi\Menu\MenuItemFactory;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigureMenuEventListener implements EventSubscriberInterface, TranslationContainerInterface
{
    /* Menu items */
    const ITEM__PUBLISH_HIDDEN = 'content_edit__sidebar_right__publish_hidden';

    public function __construct(
        private readonly MenuItemFactory $factory
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::CONTENT_CREATE_SIDEBAR_RIGHT => 'onConfigureMenu',
            ConfigureMenuEvent::CONTENT_EDIT_SIDEBAR_RIGHT => 'onConfigureMenu',
        ];
    }

    public function onConfigureMenu(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();

        $publishMenu = $menu->getChild(ContentEditRightSidebarBuilder::ITEM__PUBLISH);
        if ($publishMenu) {
            $canPublish = $publishMenu->getAttribute('disabled') !== 'disabled';
            $publishHiddenAttributes = [
                'class' => ContentEditRightSidebarBuilder::BTN_TRIGGER_CLASS,
                'data-click' => '#ezplatform_content_forms_content_edit_publish_hidden',
            ];
            $item = $this->factory->createItem(
                self::ITEM__PUBLISH_HIDDEN,
                [
                    'attributes' => $canPublish
                        ? $publishHiddenAttributes
                        : array_merge($publishHiddenAttributes, ContentEditRightSidebarBuilder::BTN_DISABLED_ATTR),
                ]
            );

            $menu->addChild($item);

            $manipulator = new MenuManipulator();
            $manipulator->moveToPosition($menu[self::ITEM__PUBLISH_HIDDEN], -1);
        }
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM__PUBLISH_HIDDEN, 'menu'))->setDesc('Publish hidden'),
        ];
    }
}
