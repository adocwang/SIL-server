<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/15/17
 * Time: 20:58
 */

namespace AppBundle\Constant;


class EnterpriseChineseKey
{
    private static $keyMap = [
        'name' => '公司名称',
        'econ_kind' => '企业类型',
        'regist_capi' => '注册资本',
        'address' => '地址',
        'reg_no' => '企业注册号',
        'scope' => '经营范围',
        'term_start' => '营业开始日期',
        'term_end' => '营业结束日期',
        'belong_org' => '所属工商局',
        'oper_name' => '法人',
        'start_date' => '成立日期',
        'end_date' => '注销日期',
        'check_date' => '核准日期',
        'status' => '在业',
        'org_no' => '组织机构号',
        'credit_no' => '统一社会信用代码',
        'province' => '省份缩写（参见附录A）',
        'city' => '城市编码',
        'domains' => '行业',
        'websites.web_name' => '公司网址名称',
        'websites.web_type' => '网址类型',
        'websites.web_url' => '网址',
        'websites.source' => '网址来源',
        'websites.seq' => '编号',
        'websites.date' => '审核时间',
        'employees.job_title' => '主要人员职位',
        'employees.name' => '主要人员姓名',
        'branches.name' => '分支机构名称',
        'changerecords.change_item' => '变更项目',
        'changerecords.change_date' => '变更日期',
        'changerecords.before_content' => '变更前内容',
        'changerecords.after_content' => '变更后内容',
        'partners.name' => '股东姓名',
        'partners.stock_type' => '股东类型',
        'partners.identify_type' => '证照/证件类型',
        'partners.identify_no' => '证照/证件号码',
        'partners.should_capi_items.shoud_capi' => '认缴出资额',
        'partners.should_capi_items.invest_type' => '出资方式',
        'partners.should_capi_items.should_capi_date' => '出资时间',
        'partners.real_capi_items.real_capi' => '实缴出资额',
        'partners.real_capi_items.invest_type' => '出资方式',
        'partners.real_capi_items.real_capi_date' => '实缴时间',
        'abnormal_items.in_reason' => '经营异常列入原因',
        'abnormal_items.in_date' => '列入日期',
        'abnormal_items.out_reason' => '移出原因',
        'abnormal_items.out_date' => '移出时间',
        'contact.address' => '地址',
        'contact.telephone' => '电话',
        'contact.email' => '邮件'
    ];

    public static function getKeyFromChinese($chinese)
    {
        $key = array_search($chinese, self::$keyMap);
        return $key;
    }

    public static function getChineseFromKey($key)
    {
        if (isset(self::$keyMap[$key])) {
            return self::$keyMap[$key];
        }
        return null;
    }
}