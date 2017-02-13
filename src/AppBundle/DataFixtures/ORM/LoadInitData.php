<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 17:15
 */

namespace AppBundle\DataFixtures\ORM;


use AppBundle\Entity\Role;
use AppBundle\Entity\Bank;
use AppBundle\Entity\ClientConfig;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Tests\Encoder\PasswordEncoder;


class LoadInitData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureInterface
{
    private $container;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //角色
        $adminRole = Role::createRole(Role::ROLE_ADMIN);

        $branchRole = Role::createRole(Role::ROLE_BRANCH_PRESIDENT);

        $endRole = Role::createRole(Role::ROLE_END_PRESIDENT);

        $cmRole = Role::createRole(Role::ROLE_CUSTOMER_MANAGER);

        //银行
        $bank = new Bank();
        $bank->setName('总行');
        $manager->persist($bank);


        //用户

        /**
         * @var $encoder PasswordEncoder
         */
        $userAdmin = new User();
        $userAdmin->setTrueName('超级管理员');
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($userAdmin, '123123');
        $userAdmin->setPhone('15828516285');
        $userAdmin->setPassword($encoded);
        $userAdmin->setToken('test_token1');
        $userAdmin->setRole($adminRole);
        $userAdmin->setBank($bank);
        $userAdmin->setState(0);
        $manager->persist($userAdmin);

        $userAdmin = new User();
        $userAdmin->setTrueName('分行行长');
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($userAdmin, '123123');
        $userAdmin->setPhone('15828516286');
        $userAdmin->setPassword($encoded);
        $userAdmin->setToken('test_token2');
        $userAdmin->setRole($branchRole);
        $userAdmin->setBank($bank);
        $userAdmin->setState(0);
        $manager->persist($userAdmin);

        $userAdmin = new User();
        $userAdmin->setTrueName('支行行长');
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($userAdmin, '123123');
        $userAdmin->setPhone('15828516287');
        $userAdmin->setPassword($encoded);
        $userAdmin->setToken('test_token3');
        $userAdmin->setRole($endRole);
        $userAdmin->setBank($bank);
        $userAdmin->setState(0);
        $manager->persist($userAdmin);

        $userAdmin = new User();
        $userAdmin->setTrueName('客户经理');
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($userAdmin, '123123');
        $userAdmin->setPhone('15828516288');
        $userAdmin->setPassword($encoded);
        $userAdmin->setToken('test_token4');
        $userAdmin->setRole($cmRole);
        $userAdmin->setBank($bank);
        $userAdmin->setState(0);
        $manager->persist($userAdmin);


        //配置
        $config = new ClientConfig();
        $config->setConfigKey('message.request_interval');
        $config->setConfigValue(60);
        $config->setType(0);
        $manager->persist($config);

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