<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileUploader
 * @package App\Services
 */
class FileUploader
{
    /**
     * @var string
     */
    private $targetDirectory;

    /**
     * FileUploader constructor.
     * @param string $targetDirectory
     */
    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();
        $file->move($this->getTargetDirectory(), $fileName);

        return $fileName;
    }

    /**
     * @return string
     */
    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    /**
     * @param $targetDirectory
     */
    public function setTargetDirectory($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

}