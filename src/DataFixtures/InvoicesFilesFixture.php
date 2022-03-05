<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;

class InvoicesFilesFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        if ($_ENV['APP_ENV'] == "test") {
            $fileSystem = new Filesystem();
            $fileSystem->copy("InvoiceTemplate.pdf", "public/TestDocuments/InvoiceTemplate.pdf");
            $fileSystem->copy("InvoiceTemplate.wrong_format", "public/TestDocuments/InvoiceTemplate.wrong_format");
        }
    }
}
