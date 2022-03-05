<?php

namespace App\DataFixtures;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SettingsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $settings = new Settings();
        $settings->setKey("default_debtor_limit");
        $settings->setValue(1000000.00);
        $manager->persist($settings);
        $settings = new Settings();
        $settings->setKey("default_debtor_currency");
        $settings->setValue("euro");
        $manager->persist($settings);
        $manager->flush();
    }
}
