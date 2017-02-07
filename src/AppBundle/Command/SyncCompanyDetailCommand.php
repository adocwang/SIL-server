<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/7/17
 * Time: 21:30
 */

namespace AppBundle\Command;


use AppBundle\Entity\Enterprise;
use AppBundle\Repository\EnterpriseRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCompanyDetailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            // 命令的名字（"bin/console" 后面的部分）
            ->setName('app:sync:company-detail')
            // the short description shown while running "php bin/console list"
            // 运行 "php bin/console list" 时的简短描述
            ->setDescription('Sync company from qixin')
            // the full command description shown when running the command with
            // the "--help" option
            // 运行命令时使用 "--help" 选项时的完整命令描述
            ->setHelp("This command allows you to Sync company details from qixin to mongodb");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var Registry $doctrine
         * @var EnterpriseRepository $enterpriseRepository
         * @var Enterprise $enterprise
         */
        $doctrine = $this->getContainer()->get('doctrine');
        $enterpriseRepository = $doctrine->getRepository('AppBundle:Enterprise');
        $enterprises = $enterpriseRepository->findAll();

        // outputs multiple lines to the console (adding "\n" at the end of each line)
        // 输出多行到控制台（在每一行的末尾添加 "\n"）
        $output->writeln([
            'Result',
            '============',
            '',
        ]);
        foreach ($enterprises as $enterprise) {
            $output->writeln($enterprise->getName() . ":" . $enterprise->getObjId());
        }
    }
}