<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class FileUploaderService
{
    private $targetDirectory;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload($subDirectory, File $file)
    {
        $fileName = time() . '_' . uniqid() . '.' . $file->guessExtension();
        try {
            $file->move($this->getTargetDirectory($subDirectory) . "/", $fileName);
        } catch (FileException $e) {
            throw new \Exception("Error in uploading document", 500);
        }

        return $fileName;
    }

    public function getTargetDirectory($subDirectory)
    {
        return $this->targetDirectory . $subDirectory;
    }
}
