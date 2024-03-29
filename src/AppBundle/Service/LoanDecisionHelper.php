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
use AppBundle\Entity\Finding;
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
        $values1 = $this->findValuesInDetail($configTemplate, $enterprise->getDetailObjId());
        $values2 = $this->findValuesInFinding($configTemplate, $enterprise);
        $values = array_merge($values1, $values2);

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

    public function formatValue($type, $value)
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

    protected function findValuesInDetail($configTemplate, $detailId)
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

    protected function findValuesInFinding($configTemplate, $enterprise)
    {
        /**
         * @var $enterpriseFinding Finding
         */
        $enterpriseFinding = $this->em->getRepository('AppBundle:Finding')->findOneByEnterprise($enterprise);
        if (empty($enterpriseFinding) || empty($enterpriseFinding->getData())) {
            return [];
        }
        $findingData = json_decode($enterpriseFinding->getData(), true);

        $values = [];
        foreach ($configTemplate as $field) {
            $value = $this->findInFindingValue($field['title'], $findingData);
            if (!empty($value))
                $values[($field['title'])] = $value;
        }
        return $values;
    }

    public function findInFindingValue($fieldName, $findingData)
    {
        foreach ($findingData['submitData'] as $project) {
            foreach ($project['content'] as $content) {
                if (trim($content['title']) == trim($fieldName)) {
                    if (is_array($content['value'])) {
                        $content['value'] = join(' ', $content['value']);
                    }
                    return $content['value'];
                }
            }
        }
        return null;
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

    public function getConfigTemplate()
    {
        /**
         * @var $configure ClientConfig
         */
        $configure = $this->em->getRepository('AppBundle:ClientConfig')->findOneByConfigKey('loan.decision_condition');
        if (empty($configure)) {
            return [];
        }
        $configureArr = json_decode($configure->getConfigValue(), true);
        $conditions = [];
        foreach ($configureArr as $configSection) {
            foreach ($configSection['condition_list'] as $condition) {
                $conditions[] = $condition;
            }
        }
        return $conditions;
    }

    public function findDetailValue($fieldName, $detail)
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