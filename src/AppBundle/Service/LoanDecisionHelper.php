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

    public function getDataForm($enterpriseId, $data = [])
    {
        /**
         * @var Enterprise $enterprise
         * @var EnterpriseDetail $enterpriseDetail
         */
        $enterprise = $this->em->getRepository('AppBundle:Enterprise')->find($enterpriseId);
        if (empty($enterprise) || empty($enterprise->getDetailObjId())) {
            return $this->buildEmptyDataForm();
        }
        $configTemplate = $this->getConfigTemplate();
        $values = $this->findValues($configTemplate, $enterprise->getDetailObjId());

        if (!empty($data)) {
            foreach ($configTemplate as $field) {
                $fieldTitle = $field['title'];
                if (!empty($this->findValueInData($fieldTitle, $data))) {
                    $values[$fieldTitle] = $this->findValueInData($fieldTitle, $data);
                }
            }
        }
        if (empty($values)) {
            return $this->buildEmptyDataForm();
        }
        $formLines = [];
        foreach ($configTemplate as $field) {
            $fieldTitle = $field['title'];
            $value = $this->formatValue($field['option_type'], $values[$fieldTitle]);
            $formLines[] = [
                'title' => $fieldTitle,
                'type' => $field['option_type'],
                'value' => $value,
                'point' => $this->calculatePoints($field, $value)];
        }
        return $formLines;
    }

    private function formatValue($type, $value)
    {
        if ($type == 'integer') {
            $matched = preg_match('/(\d|\.)+/', $value, $match);
            if ($matched) {
                $value = (float)$match[0];
            } else {
                $value = 0;
            }
        } elseif ($type == 'string') {
            $value = trim($value);
        }
        return $value;
    }

    private function findValueInData($title, $data)
    {
        foreach ($data as $field) {
            if ($field['title'] == $title) {
                return $field['value'];
            }
        }
        return '';
    }

    private function findValues($configTemplate, $detailId)
    {
        $enterpriseDetail = $this->mm->getRepository('AppBundle:EnterpriseDetail')->find($detailId);
        if (empty($enterpriseDetail) || empty($enterpriseDetail->getDetail())) {
            return [];
        }
        $detail = $enterpriseDetail->getDetail();
        $values = [];
        foreach ($configTemplate as $field) {
            $values[($field['title'])] = $this->findDetailValue($field['title'], $detail);
        }
        return $values;
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
            foreach ($templateField['options'] as $option) {
                $option['value'] = (int)trim($option['value']);
                if ($option['condition'] == '>') {
                    if ($value > $option['value']) {
                        $points = $option['point'];
                        break;
                    }
                } elseif ($option['condition'] == '<') {
                    if ($value < $option['value']) {
                        $points = $option['point'];
                        break;
                    }
                } elseif ($option['condition'] == '>=') {
                    if ($value >= $option['value']) {
                        $points = $option['point'];
                        break;
                    }
                } elseif ($option['condition'] == '<=') {
                    if ($value <= $option['value']) {
                        $points = $option['point'];
                        break;
                    }
                } elseif ($option['condition'] == '=') {
                    if ($value = $option['value']) {
                        $points = $option['point'];
                        break;
                    }
                }
            }
        }
        return $points;
    }
}