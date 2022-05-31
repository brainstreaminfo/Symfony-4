<?php

namespace App\Twig;

use Doctrine\DBAL\Exception\InvalidArgumentException;
use App\Entity\NotifiableEntity;
use App\Entity\Notification;
use App\Manager\NotificationManager;
use App\Services\NotifiableInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig_Extension;

/**
 * Twig extension to display notifications
 **/
class NotificationExtension extends Twig_Extension
{
    /**
     * @var NotificationManager
     */
    protected $notificationManager;

    /**
     * @var TokenStorage|TokenStorageInterface
     */
    protected $storage;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * NotificationExtension constructor.
     * @param NotificationManager $notificationManager
     * @param TokenStorage $storage
     * @param \Twig_Environment $twig
     */
    public function __construct(NotificationManager $notificationManager, TokenStorageInterface $storage, \Twig_Environment $twig, RouterInterface $router)
    {
        $this->notificationManager  = $notificationManager;
        $this->storage              = $storage;
        $this->twig                 = $twig;
        $this->router               = $router;
    }

    /**
     * @return array available Twig functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('mgilet_notification_render', [$this, 'render'], [
                'is_safe' => ['html']
            ]),
            new \Twig_SimpleFunction('mgilet_notification_count', [$this, 'countNotifications'], [
                'is_safe' => ['html']
            ]),
            new \Twig_SimpleFunction('mgilet_notification_unseen_count', [$this, 'countUnseenNotifications'], [
                'is_safe' => ['html']
            ]),
            new \Twig_SimpleFunction('mgilet_notification_generate_path', [$this, 'generatePath'], [
                'is_safe' => ['html']
            ])
        ];
    }

    /**
     * Rendering notifications in Twig
     *
     * @param array               $options
     * @param NotifiableInterface $notifiable
     *
     * @return null|string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function render(NotifiableInterface $notifiable, array $options = [])
    {
        if (!array_key_exists('seen', $options)) {
            $options['seen'] = true;
        }

        return $this->renderNotifications($notifiable, $options);
    }

    /**
     * Render notifications of the notifiable as a list
     *
     * @param NotifiableInterface   $notifiable
     * @param array                 $options
     *
     * @return string
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function renderNotifications(NotifiableInterface $notifiable, array $options)
    {
        if ($options['seen']) {
            $notifications = $this->notificationManager->getNotifications($notifiable);
        } else {
            $notifications = $this->notificationManager->getUnseenNotifications($notifiable);
        }

        // if the template option is set, use custom template
        $template = array_key_exists('template', $options) ? $options['template'] : 'notification/notification_list.html.twig';

        return $this->twig->render($template, [
                'notificationList' => $notifications
            ]
        );
    }

    /**
     * Display the total count of notifications for the notifiable
     *
     * @param NotifiableInterface $notifiable
     *
     * @return int
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countNotifications(NotifiableInterface $notifiable)
    {
        return $this->notificationManager->getNotificationCount($notifiable);
    }

    /**
     * Display the count of unseen notifications for this notifiable
     *
     * @param NotifiableInterface $notifiable
     *
     * @return int
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countUnseenNotifications(NotifiableInterface $notifiable)
    {
        return $this->notificationManager->getUnseenNotificationCount($notifiable);
    }

    /**
     * Returns the path to the NotificationController action
     *
     * @param                   $route
     * @param                   $notifiable
     * @param Notification|null $notification
     *
     * @return \InvalidArgumentException|string
     * @throws \RuntimeException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function generatePath($route, $notifiable, Notification $notification = null)
    {
        if ($notifiable instanceof NotifiableInterface) {
            $notifiableId = $this->notificationManager->getNotifiableEntity($notifiable)->getId();
        } elseif ($notifiable instanceof NotifiableEntity) {
            $notifiableId = $notifiable->getId();
        } else {
            throw new InvalidArgumentException('You must provide a NotifiableInterface or NotifiableEntity object');
        }

        switch ($route) {
            case 'notification_list':
                return $this->router->generate( 'notification_list', ['notifiable' => $notifiableId] );

                break;
            case 'notification_mark_as_seen':
                if (!$notification) {
                    throw new \InvalidArgumentException('You must provide a Notification Entity');
                }

                return $this->router->generate('notification_mark_as_seen',[
                        'notifiable'    => $notifiableId,
                        'notification'  => $notification->getId()
                    ]);

                break;
            case 'notification_mark_as_unseen':
                if (!$notification) {
                    throw new \InvalidArgumentException('You must provide a Notification Entity');
                }

                return $this->router->generate('notification_mark_as_unseen',[
                        'notifiable'    => $notifiableId,
                        'notification'  => $notification->getId()
                    ]);

                break;
            case 'notification_mark_all_as_seen':
                return $this->router->generate('notification_mark_all_as_seen', ['notifiable' => $notifiableId]);

                break;
            default:
                return new \InvalidArgumentException('You must provide a valid route path. Paths availables : notification_list, notification_mark_as_seen, notification_mark_as_unseen, notification_mark_all_as_seen');
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mgilet_notification';
    }
}
