<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 17:15
 */

namespace AppBundle\DataFixtures\ORM;


use AppBundle\Entity\Bank;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureInterface
{
    private $container;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $adminRole = new Role();
        $adminRole->setName('管理员');
        $adminRole->setRole('ROLE_ADMIN');
        $bank = new Bank();
        $bank->setName('总行');
        $userAdmin = new User();
        $userAdmin->setTrueName('王一博');
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($userAdmin, '12348765');
        $userAdmin->setPhone('15828516285');
        $userAdmin->setPassword($encoded);
        $userAdmin->setToken('test_token');
        $userAdmin->setCreated(new \DateTime());
        $userAdmin->setModified(new \DateTime());
        $userAdmin->setRole($adminRole);
        $userAdmin->setBank($bank);
        $userAdmin->setState(0);

        $manager->persist($adminRole);
        $manager->persist($bank);
        $manager->persist($userAdmin);
        $manager->flush();
        $this->setReference('admin_user', $userAdmin);
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }
}