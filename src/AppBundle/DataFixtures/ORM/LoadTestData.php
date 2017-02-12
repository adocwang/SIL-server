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
use AppBundle\Entity\Loan;
use AppBundle\Entity\Log;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class LoadTestData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureInterface
{
    private $container;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
//        $this->clearLoanTest($manager);
//        $this->setLoanTest($manager);
    }

    private function clearLoanTest(ObjectManager $manager)
    {

        for ($i = 0; $i < 10; $i++) {
            $enterprise = $manager->getRepository('AppBundle:Enterprise')->findOneByName('贷款测试企业' . $i);
            $loan = $manager->getRepository('AppBundle:Loan')->findOneByEnterprise($enterprise);
            if (!empty($loan)) {
                $manager->remove($loan);
            }
            if (!empty($enterprise)) {
                $manager->remove($enterprise);
            }
        }
        $bank = $manager->getRepository('AppBundle:Bank')->findOneByName('测试支行');
        if (!empty($bank)) {
            $manager->remove($bank);
        }
        $user = $manager->getRepository('AppBundle:User')->findOneByPhone('13878787878');
        if (!empty($user)) {
            $manager->remove($user);
        }
        $userB = $manager->getRepository('AppBundle:User')->findOneByPhone('13878787879');
        if (!empty($userB)) {
            $manager->remove($userB);
        }
        $manager->flush();
    }

    private function setLoanTest(ObjectManager $manager)
    {
        //添加贷款测试数据
        $cmRole = $manager->getRepository('AppBundle:Role')->findOneByRole('ROLE_CUSTOMER_MANAGER');
        $user = new User();
        $user->setTrueName('贷款测试经理');
        $user->setPhone('13878787878');
        $user->setRole($cmRole);
        $manager->persist($user);

        $userB = new User();
        $userB->setTrueName('贷款测试经理B');
        $userB->setPhone('13878787879');
        $userB->setRole($cmRole);
        $manager->persist($userB);

        $bank = new Bank();
        $bank->setName('测试支行');
        $topBank = $manager->getRepository('AppBundle:Bank')->find(1);
        $bank->setSuperior($topBank);
        $manager->persist($bank);
        for ($i = 0; $i < 10; $i++) {
            $enterprise = new Enterprise();
            $enterprise->setBank($bank);
            $enterprise->setRoleA($user);
            $enterprise->setRoleB($userB);
            $enterprise->setName('贷款测试企业' . $i);
            $enterprise->setObjId('asd' . $i);
            $manager->persist($enterprise);
            $manager->flush();

            $loan = new Loan();
            $loan->setBank($bank);
            $loan->setEnterprise($enterprise);
            $loan->setProgress(0);
            $tmpData = [
                '时间' => date('Y-m-d H:i:s'),
                '备注' => '申请贷款' . $i,
                '用户' => $user->getTrueName()
            ];
            $loan->setMoreData(json_encode($tmpData));
            $manager->persist($loan);
        }
        $manager->flush();
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