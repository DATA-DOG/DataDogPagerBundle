<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Project;

class Projects implements OrderedFixtureInterface, FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        list($go, $haskell, $php) = $manager->getRepository('AppBundle:Language')
            ->createQueryBuilder('l')
            ->orderBy('l.code', 'ASC')
            ->getQuery()
            ->getResult();

        $pager = new Project();
        $pager->setCode("pg")
            ->setName("Pager for Symfony2")
            ->setLanguage($php)
            ->setHoursSpent(6);
        $manager->persist($pager);

        $godog = new Project();
        $godog->setCode("godog")
            ->setName("Godog cucumber and behat like BDD framework for GO")
            ->setLanguage($go)
            ->setHoursSpent(80);
        $manager->persist($godog);

        $sqlmock = new Project();
        $sqlmock->setCode("sqlmock")
            ->setName("Sql driver mock for GO")
            ->setLanguage($go)
            ->setHoursSpent(40);
        $manager->persist($sqlmock);

        $xmonad = new Project();
        $xmonad->setCode('xmonad')
            ->setName("Tiling window manager")
            ->setLanguage($haskell)
            ->setHoursSpent(9999);
        $manager->persist($xmonad);

        $manager->flush();
    }

    public function getOrder()
    {
        return 5;
    }
}
