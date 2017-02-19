<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/7/17
 * Time: 21:30
 */

namespace AppBundle\Command;


use AppBundle\Constant\State;
use AppBundle\Document\Company;
use AppBundle\Document\EnterpriseDetail;
use AppBundle\Entity\Enterprise;
use AppBundle\Repository\EnterpriseRepository;
use AppBundle\Service\LoanDecisionHelper;
use AppBundle\Service\QiXinApi;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyEnterpriseFilterCommand extends ContainerAwareCommand
{
    /**
     * @var LoanDecisionHelper
     */
    private $loanDecisionHelper = null;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            // 命令的名字（"bin/console" 后面的部分）
            ->setName('app:apply:enterprise-filter')
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
         * @var QiXinApi $qixinApi
         */
        $doctrine = $this->getContainer()->get('doctrine');
        $enterpriseRepository = $doctrine->getRepository('AppBundle:Enterprise');
        $em = $doctrine->getManager();
        $mm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $enterprises = $enterpriseRepository->findAll();
        $blackList = $doctrine->getRepository('AppBundle:Blacklist')->getAllAsArr();
        $enterpriseMongoRepository = $mm->getRepository('AppBundle:EnterpriseDetail');
        $enterpriseConditionJSON = $em->getRepository('AppBundle:ClientConfig')->findOneByConfigKey('enterprise.enter_condition');
        $enterpriseCondition = json_decode($enterpriseConditionJSON->getConfigValue(), true);
        $this->loanDecisionHelper = $this->getContainer()->get('app.loan_decision_helper');
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        // 输出多行到控制台（在每一行的末尾添加 "\n"）
        $output->writeln([
            'Result',
            '============',
            '',
        ]);
        foreach ($enterprises as $enterprise) {
            if (in_array($enterprise->getName(), $blackList)) {
                $enterprise->setState(State::STATE_BLOCKED);
                $enterprise->setBlockReason('blacklist');
            }
            /**
             * @var $enterpriseDetail EnterpriseDetail
             */
            $enterpriseDetail = $enterpriseMongoRepository->find($enterprise->getDetailObjId());
            if (empty($enterpriseDetail) || !$this->checkEnterprisePass($enterpriseCondition, $enterpriseDetail->getDetail())) {
                $enterprise->setState(State::STATE_BLOCKED);
                $enterprise->setBlockReason('enter_condition');
            }
            $em->persist($enterprise);
            $em->flush();
            $output->writeln($enterprise->getName() . ":" . $enterprise->getDetailObjId());
        }
    }

    /**
     * @param $conditions
     * @param $enterpriseDetailArr
     * @return bool
     */
    private function checkEnterprisePass($conditions, $enterpriseDetailArr)
    {
        foreach ($conditions as $group) {
            if ($group['condition'] == 'all' || $group['condition'] == 'allNot') {
                $passNow = true;
            } elseif ($group['condition'] == 'one' || $group['condition'] == 'oneNot') {
                $passNow = false;
            } else {
                $passNow = false;
            }
            foreach ($group['list'] as $condition) {
                $value = $this->loanDecisionHelper->findDetailValue($condition['title'], $enterpriseDetailArr);
                $match = $this->checkCondition($condition, $value);
                if (!$match && $group['condition'] == 'all') {
                    $passNow = false;
                } elseif ($match && $group['condition'] == 'allNot') {
                    $passNow = false;
                } elseif ($match && $group['condition'] == 'one') {
                    $passNow = true;
                } elseif (!$match && $group['condition'] == 'oneNot') {
                    $passNow = true;
                }
            }
            if (!$passNow) {
                return false;
            }
        }
        return true;
    }

    private function checkCondition($condition, $value)
    {
        if ($condition['type'] == 'string') {
            $condition['value'] = $this->loanDecisionHelper->formatValue('string', $condition['value']);
            $value = $this->loanDecisionHelper->formatValue('string', $value);
            if ($condition['condition'] == '=') {
                if (strcmp($condition['value'], $value) === 0) {
                    return true;
                }
            } elseif ($condition['condition'] == '%=') {
                if (preg_match($condition['value'], $value)) {
                    return true;
                }
            } elseif ($condition['condition'] == '!=') {
                if (!preg_match($condition['value'], $value)) {
                    return true;
                }
            }
        } elseif ($condition['type'] == 'integer') {
            $condition['value'] = $this->loanDecisionHelper->formatValue('integer', $condition['value']);
            $value = $this->loanDecisionHelper->formatValue('integer', $value);
            if ($condition['condition'] == '=') {
                if ($condition['value'] == $value) {
                    return true;
                }
            } elseif ($condition['condition'] == '<') {
                if ($value < $condition['value']) {
                    return true;
                }
            } elseif ($condition['condition'] == '>') {
                if ($value > $condition['value']) {
                    return true;
                }
            } elseif ($condition['condition'] == '<=') {
                if ($value <= $condition['value']) {
                    return true;
                }
            } elseif ($condition['condition'] == '>=') {
                if ($value >= $condition['value']) {
                    return true;
                }
            }
        }
        return false;
    }
}