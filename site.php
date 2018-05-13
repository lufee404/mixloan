<?php
/**
 */
defined('IN_IA') or exit('Access Denied');
define('XUAN_MIXLOAN_DEBUG', false);
!defined('XUAN_MIXLOAN_PATH') && define('XUAN_MIXLOAN_PATH', IA_ROOT . '/addons/xuan_mixloan/');
!defined('XUAN_MIXLOAN_INC') && define('XUAN_MIXLOAN_INC', XUAN_MIXLOAN_PATH . 'inc/');
!defined('MODULE_NAME') && define('MODULE_NAME','xuan_mixloan');
!defined('STYLE_PATH') && define('STYLE_PATH', '../addons/'.MODULE_NAME.'/template/style/');
!defined('NEW_PATH') && define('NEW_PATH', STYLE_PATH.'new/');
!defined('CSS_PATH') && define('CSS_PATH', STYLE_PATH.'css/');
!defined('JS_PATH') && define('JS_PATH', STYLE_PATH.'js/');
!defined('IMG_PATH') && define('IMG_PATH', STYLE_PATH.'images/');
!defined('PIC_PATH') && define('PIC_PATH', STYLE_PATH.'picture/');
require_once XUAN_MIXLOAN_INC.'functions.php'; 
class Xuan_mixloanModuleSite extends WeModuleSite {
	public function __construct(){
		$condition =  array(
			strexists($_SERVER['REQUEST_URI'], '/app/'),
			!strexists($_SERVER['REQUEST_URI'], 'allProduct'),
			!strexists($_SERVER['REQUEST_URI'], 'apply'),
			!strexists($_SERVER['REQUEST_URI'], 'queue'),
			!strexists($_SERVER['REQUEST_URI'], 'setLevel'),
			!strexists($_SERVER['REQUEST_URI'], 'temp'),
		);
		foreach ($condition as $value) {
			if ($value == false) {
				$con = false;
				break;
			} else {
				$con = true;
			}
		}
		if ($con) {
			m('member')->checkMember();
		}
	}
	//付款结果返回
	public function payResult($params){
		global $_W, $_GPC;
		$uniacid=$_W['uniacid'];
		$fee = $params['fee'];
		$openid = m('user')->getOpenid();
		$member = m('member')->getMember($openid);
		$config = $this -> module['config'];
		if ($params['result'] == 'success') {
            if ($params['from']=='notify') {
                $user_id = pdo_fetchcolumn('select openid from '.tablename('core_paylog').'
					where tid=:tid', array(':tid'=>$params['tid']));
                $openid = pdo_fetchcolumn('select openid from '.tablename('xuan_mixloan_member').'
					where id=:id', array(':id'=>$user_id));
                $member = m('member')->getMember($openid);
            }
            if (empty($openid)) {
                message('请不要重复提交', $this->createMobileUrl('user'), 'error');
            }
			$type = substr($params['tid'],0,5);
			if ($type=='10001') {
				//认证付费
				if (empty($member['id'])) {
					header("location:{$this->createMobileUrl('user')}");
				}
				$agent = m('member')->checkAgent($member['id'], $config);
				if ($agent['code'] == 1) {
					message("您已经是会员，请不要重复提交", $this->createMobileUrl('user'), "error");
				}
				$insert = array(
						"uniacid"=>$_W["uniacid"],
						"uid"=>$member['id'],
						"createtime"=>time(),
						"tid"=>$params['tid'],
						"fee"=>$fee,
				);
				pdo_insert("xuan_mixloan_payment", $insert);
				pdo_update("xuan_mixloan_member", array('level'=>1), array('id'=>$member['id']));
				$inviter = m('member')->getInviter($member['phone'], $openid);
				if ($inviter) {
					$agent = m('member')->checkAgent($inviter, $config);
					if ($agent['level'] == 1) {
						$re_bonus = $config['inviter_fee_one_init'];
					} else if ($agent['level'] == 2) {
						$re_bonus = $config['inviter_fee_one_mid'];
					} else if ($agent['level'] == 3) {
						$re_bonus = $config['inviter_fee_one_height'];
					}
					if ($re_bonus) {
						$insert_i = array(
							'uniacid' => $_W['uniacid'],
							'uid' => $member['id'],
							'phone' => $member['phone'],
							'certno' => $member['certno'],
							'realname' => $member['realname'],
							'inviter' => $inviter,
							'extra_bonus'=>0,
							'done_bonus'=>0,
							're_bonus'=>$re_bonus,
							'status'=>2,
							'createtime'=>time(),
							'degree'=>1
						);
						pdo_insert('xuan_mixloan_product_apply', $insert_i);
					}
					//二级
					$man = m('member')->getInviterInfo($inviter);
					$inviter = m('member')->getInviter($man['phone'], $man['openid']);
					if ($inviter) {
						$agent = m('member')->checkAgent($inviter, $config);
						if ($agent['level'] == 1) {
							$re_bonus = $config['inviter_fee_two_init'];
						} else if ($agent['level'] == 2) {
							$re_bonus = $config['inviter_fee_two_mid'];
						} else if ($agent['level'] == 3) {
							$re_bonus = $config['inviter_fee_two_height'];
						}
						if ($re_bonus) {
							$insert_i = array(
								'uniacid' => $_W['uniacid'],
								'uid' => $member['id'],
								'phone' => $member['phone'],
								'certno' => $member['certno'],
								'realname' => $member['realname'],
								'inviter' => $inviter,
								'extra_bonus'=>0,
								'done_bonus'=>0,
								're_bonus'=>$re_bonus,
								'status'=>2,
								'createtime'=>time(),
								'degree'=>2
							);
							pdo_insert('xuan_mixloan_product_apply', $insert_i);
						}
					}
				}
				message("支付成功", $this->createMobileUrl('user'), "success");
			}
		}
		if (empty($params['result']) || $params['result'] != 'success') {
			//此处会处理一些支付失败的业务代码
			message("出错啦", $this->createMobileUrl('user'), "error");
		}
	}
}