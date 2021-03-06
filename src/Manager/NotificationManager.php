<?php

namespace App\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use App\Entity\NotifiableEntity;
use App\Entity\NotifiableNotification;
use App\Entity\Notification;
use App\EventListeners\NotificationEvent;
use App\Services\MgiletNotificationEvents;
use App\Services\NotifiableDiscovery;
use App\Services\NotifiableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotificationManager
 * Manager for notifications
 * @package App\Manager
 */
class NotificationManager
{
    /**
     * @var NotifiableDiscovery
     */
    protected $discovery;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var NotifiableRepository
     */
    protected $notifiableRepository;

    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;

    /**
     * @var NotifiableNotificationRepository
     */
    protected $notifiableNotificationRepository;

    /**
     * NotificationManager constructor.
     *
     * @param EntityManager       $em
     * @param NotifiableDiscovery $discovery
     *
     */
    public function __construct(EntityManager $entityManager,  NotifiableDiscovery $discovery)
    {
        $this->em                                   = $entityManager;
        $this->discovery                            = $discovery;
        $this->dispatcher                           = $entityManager->get('event_dispatcher');
        $this->notifiableRepository                 = $this->em->getRepository('App:NotifiableEntity');
        $this->notificationRepository               = $this->em->getRepository('App:Notification');
        $this->notifiableNotificationRepository     = $this->em->getRepository('App:NotifiableNotification');
    }

    /**
     * Returns a list of available workers.
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getDiscoveryNotifiables()
    {
        return $this->discovery->getNotifiables();
    }

    /**
     * Returns one notifiable by name
     *
     * @param $name
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getNotifiable($name)
    {
        $notifiables = $this->getDiscoveryNotifiables();

        if (isset($notifiables[$name])) {
            return $notifiables[$name];
        }
        throw new \RuntimeException('Notifiable not found.');
    }

    /**
     * Get the name of the notifiable
     *
     * @param NotifiableInterface $notifiable
     *
     * @return string|null
     */
    public function getNotifiableName(NotifiableInterface $notifiable)
    {
        return $this->discovery->getNotifiableName($notifiable);
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getNotifiableIdentifier(NotifiableInterface $notifiable)
    {
        $name = $this->getNotifiableName($notifiable);

        return $this->getNotifiable($name)['identifiers'];
    }

    /**
     * Get the identifier mapping for a NotifiableEntity
     *
     * @param NotifiableEntity $notifiableEntity
     *
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getNotifiableEntityIdentifiers(NotifiableEntity $notifiableEntity)
    {
        $discoveryNotifiables = $this->getDiscoveryNotifiables();

        foreach ($discoveryNotifiables as $notifiable) {
            if ($notifiable['class'] === $notifiableEntity->getClass()) {
                return $notifiable['identifiers'];
            }
        }

        throw new \RuntimeException('Unable to get the NotifiableEntity identifiers. This could be an Entity mapping issue');
    }

    /**
     * Generates the identifier value to store a NotifiableEntity
     *
     * @param NotifiableInterface $notifiable
     *
     * @return string
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function generateIdentifier(NotifiableInterface $notifiable)
    {
        $Notifiableidentifiers  = $this->getNotifiableIdentifier($notifiable);
        $identifierValues       = [];

        foreach ($Notifiableidentifiers as $identifier) {
            $method             = sprintf('get%s', ucfirst($identifier));
            $identifierValues[] = $notifiable->$method();
        }

        return implode('-', $identifierValues);
    }

    /**
     * Get a NotifiableEntity form a NotifiableInterface
     *
     * @param NotifiableInterface $notifiable
     *
     * @return NotifiableEntity
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getNotifiableEntity(NotifiableInterface $notifiable)
    {
        $identifier     = $this->generateIdentifier($notifiable);
        $class          = ClassUtils::getRealClass(get_class($notifiable));
        $entity         = $this->notifiableRepository->findOneBy([
            'identifier'    => $identifier,
            'class'         => $class
        ]);

        if (!$entity) {
            $entity = new NotifiableEntity($identifier, $class);
            $this->em->persist($entity);
            $this->em->flush();
        }

        return $entity;
    }

    /**
     * @param NotifiableEntity $notifiableEntity
     *
     * @return NotifiableInterface
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getNotifiableInterface(NotifiableEntity $notifiableEntity)
    {
        return $this->notifiableRepository->findNotifiableInterface(
            $notifiableEntity,
            $this->getNotifiableEntityIdentifiers($notifiableEntity)
        );
    }

    /**
     * @param $id
     *
     * @return NotifiableEntity|null
     */
    public function getNotifiableEntityById($id)
    {
        return $this->notifiableRepository->findOneById($id);
    }

    /**
     * @param NotifiableInterface $notifiable
     * @param Notification        $notification
     *
     * @return NotifiableNotification|null
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getNotifiableNotification(NotifiableInterface $notifiable, Notification $notification)
    {
        return $this->notifiableNotificationRepository->findOne(
            $notification->getId(),
            $this->getNotifiableEntity($notifiable)
        );
    }

    /**
     * Avoid code duplication
     *
     * @param $flush
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function flush($flush)
    {
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Get all notifications
     *
     * @return Notification[]
     */
    public function getAll()
    {
        return $this->notificationRepository->findAll();
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return array
     */
    public function getNotifications(NotifiableInterface $notifiable)
    {
        return $this->notificationRepository->findAllByNotifiable(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable))
        );
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getUnseenNotifications(NotifiableInterface $notifiable)
    {
        return $this->notificationRepository->findAllByNotifiable(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable)),
            false
        );
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getSeenNotifications(NotifiableInterface $notifiable)
    {
        return $this->notificationRepository->findAllByNotifiable(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable)),
            true
        );
    }


    /**
     * Get one notification by id
     *
     * @param $id
     *
     * @return Notification
     */
    public function getNotification($id)
    {
        return $this->notificationRepository->findOneById($id);
    }

    /**
     * @param string $subject
     * @param string $message
     * @param string $link
     *
     * @return Notification
     */
    public function createNotification($subject, $message = null, $link = null)
    {
        $notification = new Notification();
        $notification
            ->setSubject($subject)
            ->setMessage($message)
            ->setLink($link)
        ;

        $event = new NotificationEvent($notification);
        $this->dispatcher->dispatch(MgiletNotificationEvents::CREATED, $event);

        return $notification;
    }

    /**
     * Add a Notification to a list of NotifiableInterface entities
     *
     * @param NotifiableInterface[] $notifiables
     * @param Notification          $notification
     * @param bool                  $flush
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function addNotification($notifiables, Notification $notification, $flush = false)
    {
        foreach ($notifiables as $notifiable) {
            $entity                 = $this->getNotifiableEntity($notifiable);
            $notifiableNotification = new NotifiableNotification();
            $event                  = new NotificationEvent($notification, $notifiable);

            $entity->addNotifiableNotification($notifiableNotification);
            $notification->addNotifiableNotification($notifiableNotification);
            $this->dispatcher->dispatch(MgiletNotificationEvents::ASSIGNED, $event);
        }

        $this->flush($flush);
    }

    /**
     * Deletes the link between a Notifiable and a Notification
     *
     * @param array        $notifiables
     * @param Notification $notification
     * @param bool         $flush
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function removeNotification(array $notifiables, Notification $notification, $flush = false)
    {
        $repo = $this->em->getRepository('App:NotifiableNotification');

        foreach ($notifiables as $notifiable) {
            $repo->createQueryBuilder('nn')
                ->delete()
                ->where('nn.notifiableEntity = :entity')
                ->andWhere('nn.notification = :notification')
                ->setParameter('entity', $this->getNotifiableEntity($notifiable))
                ->setParameter('notification', $notification)
                ->getQuery()
                ->execute()
            ;

            $event = new NotificationEvent($notification, $notifiable);
            $this->dispatcher->dispatch(MgiletNotificationEvents::REMOVED, $event);
        }

        $this->flush($flush);
    }

    /**
     * @param Notification $notification
     *
     * @param bool         $flush
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteNotification(Notification $notification, $flush = false)
    {
        $this->em->remove($notification);
        $this->flush($flush);
        $event = new NotificationEvent($notification);
        $this->dispatcher->dispatch(MgiletNotificationEvents::DELETED, $event);
    }

    /**
     * @param NotifiableInterface $notifiable
     * @param Notification        $notification
     * @param bool                $flush
     *
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function markAsSeen(NotifiableInterface $notifiable, Notification $notification, $flush = false)
    {
        $nn = $this->getNotifiableNotification($notifiable, $notification);

        if ($nn) {
            $nn->setSeen(true);
            $event = new NotificationEvent($notification, $notifiable);
            $this->dispatcher->dispatch(MgiletNotificationEvents::SEEN, $event);
            $this->flush($flush);
        } else {
            throw new EntityNotFoundException('The link between the notifiable and the notification has not been found');
        }
    }

    /**
     * @param NotifiableInterface $notifiable
     * @param Notification        $notification
     * @param bool                $flush
     *
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function markAsUnseen(NotifiableInterface $notifiable, Notification $notification, $flush = false)
    {
        $nn = $this->getNotifiableNotification($notifiable, $notification);

        if ($nn) {
            $nn->setSeen(false);
            $event = new NotificationEvent($notification, $notifiable);
            $this->dispatcher->dispatch(MgiletNotificationEvents::UNSEEN, $event);
            $this->flush($flush);
        } else {
            throw new EntityNotFoundException('The link between the notifiable and the notification has not been found');
        }
    }

    /**
     * @param NotifiableInterface $notifiable
     * @param bool                $flush
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function markAllAsSeen(NotifiableInterface $notifiable, $flush = false)
    {
        $nns = $this->notifiableNotificationRepository->findAllForNotifiable(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable))
        );

        foreach ($nns as $nn) {
            $nn->setSeen(true);
            $event = new NotificationEvent($nn->getNotification(), $notifiable);
            $this->dispatcher->dispatch(MgiletNotificationEvents::SEEN, $event);
        }

        $this->flush($flush);
    }

    /**
     * @param NotifiableInterface $notifiable
     * @param Notification        $notification
     *
     * @return bool
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws EntityNotFoundException
     */
    public function isSeen(NotifiableInterface $notifiable, Notification $notification)
    {
        $nn = $this->getNotifiableNotification($notifiable, $notification);

        if ($nn) {
            return $nn->isSeen();
        }
        throw new EntityNotFoundException('The link between the notifiable and the notification has not been found');
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @return int
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getNotificationCount(NotifiableInterface $notifiable)
    {
        return $this->notifiableNotificationRepository->getNotificationCount(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable))
        );
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @return int
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getUnseenNotificationCount(NotifiableInterface $notifiable)
    {
        return $this->notifiableNotificationRepository->getNotificationCount(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable)),
            false
        );
    }

    /**
     * @param NotifiableInterface $notifiable
     *
     * @return int
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getSeenNotificationCount(NotifiableInterface $notifiable)
    {
        return $this->notifiableNotificationRepository->getNotificationCount(
            $this->generateIdentifier($notifiable),
            ClassUtils::getRealClass(get_class($notifiable)),
            true
        );
    }

    /**
     * @param Notification $notification
     * @param \DateTime    $dateTime
     * @param bool         $flush
     *
     * @return Notification
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setDate(Notification $notification, \DateTime $dateTime, $flush = false)
    {
        $notification->setDate($dateTime);
        $this->flush($flush);
        $event = new NotificationEvent($notification);
        $this->dispatcher->dispatch(MgiletNotificationEvents::MODIFIED, $event);

        return $notification;
    }

    /**
     * @param Notification $notification
     * @param string       $subject
     * @param bool         $flush
     *
     * @return Notification
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setSubject(Notification $notification, $subject, $flush = false)
    {
        $notification->setSubject($subject);
        $this->flush($flush);
        $event = new NotificationEvent($notification);
        $this->dispatcher->dispatch(MgiletNotificationEvents::MODIFIED, $event);

        return $notification;
    }

    /**
     * @param Notification $notification
     * @param string       $subject
     * @param bool         $flush
     *
     * @return Notification
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setMessage(Notification $notification, $subject, $flush = false)
    {
        $notification->setSubject($subject);
        $this->flush($flush);
        $event = new NotificationEvent($notification);
        $this->dispatcher->dispatch(MgiletNotificationEvents::MODIFIED, $event);

        return $notification;
    }

    /**
     * @param Notification $notification
     * @param string       $link
     * @param bool         $flush
     *
     * @return Notificatio
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setLink(Notification $notification, $link, $flush = false)
    {
        $notification->setLink($link);
        $this->flush($flush);
        $event = new NotificationEvent($notification);
        $this->dispatcher->dispatch(MgiletNotificationEvents::MODIFIED, $event);

        return $notification;
    }

    /**
     * @param Notification $notification
     *
     * @return NotifiableInterface[]
     */
    public function getNotifiables(Notification $notification)
    {
        return $this->notifiableRepository->findAllByNotification($notification);
    }

    /**
     * @param Notification $notification
     *
     * @return NotifiableInterface[]
     */
    public function getUnseenNotifiables(Notification $notification)
    {
        return $this->notifiableRepository->findAllByNotification($notification, true);
    }

    /**
     * @param Notification $notification
     *
     * @return NotifiableInterface[]
     */
    public function getSeenNotifiables(Notification $notification)
    {
        return $this->notifiableRepository->findAllByNotification($notification, false);
    }
}
