<?php
ini_set('date.timezone','Asia/Shanghai');
define("SDK_BASE", dirname(__FILE__));
define("ADAPAY_CORE", SDK_BASE."/../AdapayCore");
define("SDK_VERSION", "v1.4.3");
define("GATE_WAY_URL", "https://%s.adapay.tech");
define("DEBUG", true);
define("LOG", dirname(SDK_BASE)."/log/prod");
define("ENV", "prod");

include_once ADAPAY_CORE."/AdaPay.php";
include_once ADAPAY_CORE."/AdaLoader.php";

# *辅助接口
include_once SDK_BASE."/Utils/SDKTools.php";
# 工具类 AdapayTools
include_once SDK_BASE."/AdapayTools.php";

# *聚合支付
# 支付对象
include_once SDK_BASE."/Payment.php";
# 退款对象
include_once SDK_BASE."/Refund.php";
# 支付确认对象
include_once SDK_BASE."/PaymentConfirm.php";
# 支付撤销对象
include_once SDK_BASE."/PaymentReverse.php";

# 个人用户
include_once SDK_BASE."/Member.php";
# 企业用户
include_once SDK_BASE."/CorpMember.php";
# 结算账户
include_once SDK_BASE."/SettleAccount.php";


# * 钱包收银台
# 取现对象 Drawcash
include_once SDK_BASE."/Drawcash.php";
# 账户对象 Account
include_once SDK_BASE."/Account.php";
# 收银台对象 Checkout
include_once SDK_BASE."/Checkout.php";
# 钱包 Wallet
include_once SDK_BASE."/Wallet.php";

# 转账交易
include_once  SDK_BASE. "/SettleAccountTransfer.php";
include_once  SDK_BASE. "/SettleAccountCommissions.php";
# 冻结、解冻账号
include_once SDK_BASE. "/FreezeAccount.php";
include_once SDK_BASE. "/UnFreezeAccount.php";
# 快捷支付
include_once SDK_BASE. "/FastPay.php";
include_once SDK_BASE. "/FastPayCard.php";


