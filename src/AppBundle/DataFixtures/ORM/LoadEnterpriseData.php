<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 17:15
 */

namespace AppBundle\DataFixtures\ORM;


use AppBundle\Entity\Bank;
use AppBundle\Entity\Enterprise;
use AppBundle\Entity\Message;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Entity\VCCompany;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class LoadEnterpriseData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureInterface
{
    private $container;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $vcCompany = new VCCompany();
        $vcCompany->setName('小米科技有限责任公司');
        $vcCompany->setVcName('小米资本');
        $manager->persist($vcCompany);
        $manager->flush();
        for ($i = 1; $i < 2; $i++) {
            $enterprise = new Enterprise();
            $enterprise->setName('测试' . $i . '科技有限公司');
            $enterprise->setAddress('时间戳市' . $i . '秒');
            $enterprise->setLegalMan('王老' . $i);
            $enterprise->setStart(new \DateTime('2009-1-' . $i));
            $enterprise->setObjId('df98dfg-1324j81-test' . $i);
            $manager->persist($enterprise);
            $manager->flush();
        }
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
        return 5;
    }
}