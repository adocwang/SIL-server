<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/7/17
 * Time: 21:30
 */

namespace AppBundle\Command;


use AppBundle\Entity\Enterprise;
use AppBundle\Entity\VCCompany;
use AppBundle\Repository\EnterpriseRepository;
use AppBundle\Repository\VCCompanyRepository;
use AppBundle\Service\HttpClient;
use AppBundle\Service\QiXinApi;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use EightPoints\Bundle\GuzzleBundle\GuzzleBundle;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncVCCompanyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            // 命令的名字（"bin/console" 后面的部分）
            ->setName('app:sync:vc-company')
            // the short description shown while running "php bin/console list"
            // 运行 "php bin/console list" 时的简短描述
            ->setDescription('get children of vc-company')
            // the full command description shown when running the command with
            // the "--help" option
            // 运行命令时使用 "--help" 选项时的完整命令描述
//            ->setHelp("This command allows you to Sync company details from qixin to mongodb")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var Registry $doctrine
         * @var VCCompanyRepository $enterpriseRepository
         * @var EnterpriseRepository $enterpriseRepository
         * @var VCCompany $vcCompany
         * @var QiXinApi $qixinApi
         * @var ManagerRegistry $mongo
         */
        $doctrine = $this->getContainer()->get('doctrine');
        $vcCompanyRepository = $doctrine->getRepository('AppBundle:VCCompany');
        $enterpriseRepository = $doctrine->getRepository('AppBundle:Enterprise');
        $em = $doctrine->getManager();
        $vcCompanies = $vcCompanyRepository->findAll();

        // outputs multiple lines to the console (adding "\n" at the end of each line)
        // 输出多行到控制台（在每一行的末尾添加 "\n"）
        $output->writeln([
            'Result',
            '============',
            '',
        ]);
        $qixinApi = $this->getContainer()->get('app.qixin_api');
        foreach ($vcCompanies as $vcCompany) {
            $i = 0;
            do {
                try {
                    $enterprisesArr = $qixinApi->getChildCompany($vcCompany->getName());
                } catch (ConnectException $e) {
                    continue;
                }
            } while (++$i < 5);
//            print_r($enterprisesArr);exit;
            if (!empty($enterprisesArr)) {
                foreach ($enterprisesArr as $enterpriseArr) {
                    if ($enterpriseRepository->findOneByName($enterpriseArr['name'])) {
                        continue;
                    }
                    $enterprise = new Enterprise();
                    $enterprise->setName($enterpriseArr['name']);
                    $enterprise->setStart(new \DateTime($enterpriseArr['start_date']));
                    $enterprise->setLegalMan($enterpriseArr['oper_name']);
                    $enterprise->setObjId($enterpriseArr['id']);
                    $em->persist($enterprise);
                    $em->flush();
                }
            }else{
                $output->writeln($vcCompany->getName().'is empty');
            }
            $vcCompany->setChildrenSynced(new \DateTime());
            $em->persist($vcCompany);
            $em->flush();
            $output->writeln($vcCompany->getName());
        }

    }
}