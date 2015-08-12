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
        $langs = $manager->getRepository('AppBundle:Language')
            ->createQueryBuilder('l')
            ->orderBy('l.code', 'ASC')
            ->getQuery()
            ->getResult();

        list($go, $haskell, $php) = $langs;

        $pager = new Project();
        $pager->setCode("pg")
            ->setName("Pager for Symfony2")
            ->setLanguage($php)
            ->setDeadline(8)
            ->setHoursSpent(6);
        $manager->persist($pager);

        $godog = new Project();
        $godog->setCode("godog")
            ->setName("Godog cucumber and behat like BDD framework for GO")
            ->setLanguage($go)
            ->setDeadline(60)
            ->setHoursSpent(80);
        $manager->persist($godog);

        $sqlmock = new Project();
        $sqlmock->setCode("sqlmock")
            ->setName("Sql driver mock for GO")
            ->setLanguage($go)
            ->setDeadline(60)
            ->setHoursSpent(40);
        $manager->persist($sqlmock);

        $xmonad = new Project();
        $xmonad->setCode('xmonad')
            ->setName("Tiling window manager")
            ->setLanguage($haskell)
            ->setDeadline(1500)
            ->setHoursSpent(9999)
            ->setStatus(Project::CLOSED);
        $manager->persist($xmonad);

        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 250; $i++) {
            $project = new Project();
            $project->setLanguage($faker->randomElement($langs));
            $project->setCode($faker->word);
            $project->setName($faker->sentence(5));
            $project->setHoursSpent($faker->numberBetween(1, 100));
            $project->setDeadline($project->getHoursSpent() + $faker->numberBetween(-10, 30));
            $project->setStatus($faker->numberBetween(Project::OPEN, Project::CLOSED));

            $manager->persist($project);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 5;
    }
}
