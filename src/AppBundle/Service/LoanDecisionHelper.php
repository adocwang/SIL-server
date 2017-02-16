<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 15:29
 */

namespace AppBundle\Service;


use AppBundle\Constant\EnterpriseChineseKey;
use AppBundle\Document\EnterpriseDetail;
use AppBundle\Entity\ClientConfig;
use AppBundle\Entity\Enterprise;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ORM\EntityManager;

class LoanDecisionHelper
{
    private $em;
    private $mm;

    public function __construct(EntityManager $em, ManagerRegistry $mm)
    {
        $this->em = $em;
        $this->mm = $mm;
    }

    public function getDataForm($enterpriseId)
    {
        /**
         * @var Enterprise $enterprise
         * @var EnterpriseDetail $enterpriseDetail
         */
        $enterprise = $this->em->getRepository('AppBundle:Enterprise')->find($enterpriseId);
        if (empty($enterprise) || empty($enterprise->getDetailObjId())) {
            return $this->buildEmptyDataForm();
        }
        $enterpriseDetail = $this->mm->getRepository('AppBundle:EnterpriseDetail')->find($enterprise->getDetailObjId());
        if (empty($enterpriseDetail) || empty($enterpriseDetail->getDetail())) {
            return $this->buildEmptyDataForm();
        }
        $detail = $enterpriseDetail->getDetail();
        $configTemplate = $this->getConfigTemplate();
        $formLines = [];
        foreach ($configTemplate as $field) {
            $value = $this->findValue($field['title'], $detail);
            $formLines[] = [
                'title' => $field['title'],
                'type' => $field['option_type'],
                'value' => $value,
                'point' => $this->calculatePoints($field, $value)];
        }
        return $formLines;
    }

    private function findValue($fieldName, $detail)
    {
        return $this->findDetailValue($fieldName, $detail);
    }

    private function buildEmptyDataForm()
    {
        $configTemplate = $this->getConfigTemplate();
        $formLines = [];
        foreach ($configTemplate as $field) {
            $formLines[] = [
                'title' => $field['title'],
                'type' => $field['option_type'],
                'value' => '',
                'point' => 0
            ];
        }
        return $formLines;
    }

    private function getConfigTemplate()
    {
        /**
         * @var $configure ClientConfig
         */
        $configure = $this->em->getRepository('AppBundle:ClientConfig')->findOneByConfigKey('loan.decision_condition');
        if (empty($configure)) {
            return [];
        }
        return json_decode($configure->getConfigValue(), true);
    }

    private function findDetailValue($fieldName, $detail)
    {
        $keyEncoded = EnterpriseChineseKey::getKeyFromChinese($fieldName);
        $keys = explode(',', $keyEncoded);
        foreach ($keys as $key) {
            if (isset($detail[$key])) {
                $detail = $detail[$key];
            } else {
                $detail = "";
                break;
            }
        }
        return $detail;
    }

    /**
     * 计算分数
     * @param $templateField
     * @param $value
     * @return integer
     */
    private function calculatePoints($templateField, $value)
    {
        $value = trim($value);
        $points = (int)$templateField['default_point'];
        if ($templateField['option_type'] == "string") {
            foreach ($templateField['options'] as $option) {
                $option['value'] = trim($option['value']);
                if ($option['condition'] == 'like') {
                    if (preg_match('/' . $option['value'] . '/', $value)) {
                        $points = $option['point'];
                    }
                } elseif ($option['condition'] == '=') {
                    if ($option['value'] == $value) {
                        $points = $option['point'];
                    }
                }
            }
        } elseif ($templateField['option_type'] == "integer") {
            $value = (int)$value;
            foreach ($templateField['options'] as $option) {
                $option['value'] = (int)trim($option['value']);
                if ($option['condition'] == '>') {
                    if ($option['value'] > $value) {
                        $points = $option['point'];
                    }
                } elseif ($option['condition'] == '<') {
                    if ($option['value'] < $value) {
                        $points = $option['point'];
                    }
                } elseif ($option['condition'] == '>=') {
                    if ($option['value'] >= $value) {
                        $points = $option['point'];
                    }
                } elseif ($option['condition'] == '<=') {
                    if ($option['value'] <= $value) {
                        $points = $option['point'];
                    }
                } elseif ($option['condition'] == '=') {
                    if ($option['value'] = $value) {
                        $points = $option['point'];
                    }
                }
            }
        }
        return $points;
    }
}