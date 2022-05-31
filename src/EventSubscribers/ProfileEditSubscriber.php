<?php

namespace App\EventSubscribers;

use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Event Listener to change redirection after password change successfully.
 *
 * Class ChangePasswordListener
 * @package App\EventListeners
 */
class ProfileEditSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * ChangePasswordListener constructor.
     * @param UrlGeneratorInterface $router
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::CHANGE_PASSWORD_SUCCESS  => 'onPasswordResettingSuccess',
            FOSUserEvents::PROFILE_EDIT_SUCCESS     => 'onProfileEditSuccess'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onPasswordResettingSuccess(FormEvent $event)
    {
        $url = $this->router->generate('fos_user_change_password');
        $event->setResponse(new RedirectResponse($url));
    }

    /**
     * @param FormEvent $event
     */
    public function onProfileEditSuccess(FormEvent $event)
    {
        $url = $this->router->generate('fos_user_profile_edit');
        $event->setResponse(new RedirectResponse($url));
    }
}