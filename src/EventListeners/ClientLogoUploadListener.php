<?php

namespace App\EventListeners;

use App\Entity\Client;
use App\Services\FileUploader;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ClientLogoUploadListener
 * @package App\EventListeners
 */
class ClientLogoUploadListener
{
    /**
     * @var FileUploader
     */
    private $uploader;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ClientLogoUploadListener constructor.
     * @param FileUploader $uploader
     * @param ContainerInterface $container
     *
     */
    public function __construct(FileUploader $uploader, ContainerInterface $container)
    {
        $this->uploader     = $uploader;
        $this->container    = $container;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->uploadFile($entity);
    }

    /**
     * @param $entity
     */
    private function uploadFile($entity)
    {
        // upload only works for Client entities
        if (!$entity instanceof Client) {
            return false;
        }
        $this->uploader->setTargetDirectory($this->container->getParameter('clients_directory'));
        $file = $entity->getClientLogo();

        // for only upload new files
        if ($file instanceof UploadedFile) {
            $fileName = $this->uploader->upload($file);
            $entity->setClientLogo($fileName);
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @return bool
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        // Retrieve Form as Entity
        $entity = $args->getEntity();

        // This logic only works for Product entities
        if (!$entity instanceof Client) {
            return false;
        }

        // Check which fields were changes
        $changes = $args->getEntityChangeSet();
        // Declare a variable that will contain the name of the previous file, if exists.
        $previousFilename   = null;
        $currentFileName    = null;

        // Verify if the ClientLogo field was changed
        if (array_key_exists("clientLogo", $changes)) {
            // Update previous file name
            $previousFilename = $changes["clientLogo"][0];
            if(isset($changes['clientLogo'][1]) && $changes['clientLogo'][1] instanceof File) {
                $currentFileName = $changes['clientLogo'][1]->getFileName();
            }
        }

        // If no new clientLogo file was uploaded
        if (is_null($entity->getClientLogo())) {
            // Let original filename in the entity
            $entity->setClientLogo($previousFilename);
        } else {
            if(!is_null($previousFilename) && !is_null($currentFileName) && $previousFilename != $currentFileName) {
                $pathPreviousFile = $this->uploader->getTargetDirectory() . "/" . $previousFilename;

                // Remove it
                if (file_exists($pathPreviousFile)) {
                    unlink($pathPreviousFile);
                }
                // Upload new file
                $this->uploadFile($entity);
            } elseif(is_null($previousFilename) && !is_null($currentFileName)) {
                // Upload new file
                $this->uploadFile($entity);
            } elseif($currentFileName == $previousFilename && !is_null($previousFilename)) {
                $entity->setClientLogo($previousFilename);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof Client) {
            return false;
        }
        $this->uploader->setTargetDirectory($this->container->getParameter('clients_directory'));
        $fileName = $entity->getClientLogo();

        if (!empty($fileName) && file_exists($this->uploader->getTargetDirectory() . '/' . $fileName)) {
            $entity->setClientLogo(
                new File($this->uploader->getTargetDirectory() . '/' . $fileName)
            );
        }
    }
}