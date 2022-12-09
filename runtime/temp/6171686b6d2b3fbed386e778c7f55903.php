<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:80:"D:\phpstudy_pro\WWW\jdb_ey3pdL\public/../application/index\view\index\index.html";i:1669025881;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>MMMBCS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/swiper-bundle.min.css">
    <script src="/assets/js/jquery-2.2.2.min.js"></script>
    <script src="/assets/js/swiper-bundle.min.js"></script>
</head>
<body>
   
<div class="mainbox indexbody" style="background: #feeebd">
    <div class="header">
        <div class="hl">
            <a href="/index.html" class="logo"><img src="/assets/images/logo.png" alt=""></a>
            <span class="username">0x29******D421</span>
        </div>
        <div class="hr">
            <div class="language">
                <i class="logo_google"><img src="/assets/images/google.png" alt=""></i>
                <span>中文（简体）</span>
                <i class="icon_down"></i>
            </div>
            <a href="javascript:;" class="navicon">
                <b class="line"></b>
                <b class="line"></b>
                <b class="line"></b>
            </a>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="navbg"></div>
    
    
    <button type="button" lang='cn' class="btn" onclick="lang11('zh-cn')">中文</button>
    <button type="button" lang='en' class="btn" onclick="lang11('zh-tw')">英文</button>
    <button type="button" lang='其他语言' class="btn" >其他语言</button>
    
    <div class="navbox">
        <ul>
            <li><a href="/index.html"><?php echo __('Dashboard'); ?></a></li>
            <li class="snav">
                <div class="title_bg">
                    <a href="javascript:;"><?php echo __('Participants'); ?></a>
                </div>
                <dl class="nav01">
                    <dd><a href="recommend.html"><?php echo __('Referral'); ?></a></dd>
                </dl>
            </li>
            <li class="snav">
                <div class="title_bg">
                    <a href="javascript:;"><?php echo __('Referral'); ?></a>
                </div>
                <dl class="nav01">
                    <dd><a href="mine.html"><?php echo __('Referral'); ?></a></dd>
                    <dd><a href="letters.html"><?php echo __('My letters of happiness'); ?></a></dd>
                </dl>
            </li>
            <li><a href="promotion.html"><?php echo __('Promotion'); ?></a></li>
            <li><a href="mavro.html"><?php echo __('Mavro'); ?></a></li>
            <li><a href="account.html"><?php echo __('Account'); ?></a></li>
            <li><a href="message.html"><?php echo __('News'); ?></a></li>
        </ul>
    </div>

    <div class="i01">
        <span><?php echo __('WARNING! THIS IS A COMMUNITY OF MUTUAL FINANCIAL HELP!'); ?></span>
        <span><?php echo __('Participate only with spare money. Do not contribute all the money you have'); ?></span>
    </div>

    <div class="banner">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
              <div class="swiper-slide"><img src="/assets/images/b1.png" alt=""></div>
              <div class="swiper-slide"><img src="/assets/images/b2.png" alt=""></div>
              <div class="swiper-slide"><img src="/assets/images/b3.png" alt=""></div>
            </div>
        </div>
    </div>

    <div class="i02">
        <ul>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/11.png" alt=""></i>
                    <span>0.0 $</span>
                    <span class="name"><?php echo __('Jackpot Funds'); ?></span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2> <?php echo __('The Jackpot Funds Instruction'); ?></h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1><?php echo __('0.5% - The Jackpot Fundsv'); ?></h1>
                            <p>
                               <?php echo __('fundTips'); ?>
                            </p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>            
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/12.png" alt=""></i>
                    <span>0.0 $</span>
                    <span class="name">      <?php echo __('Funds of Refund'); ?></span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2><?php echo __('Funds of Refund Instruction'); ?></h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1><?php echo __('0.5% - The Funds of Refund'); ?></h1>
                            <p>头奖资金分配后，未获得最终奖励的帮助提供者将继续按时间倒序分配退款资金，用于支付和转账，100%返还帮助金额，直至资金到位。 退款已全部分配。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/13.png" alt=""></i>
                    <span>0.0 $</span>
                    <span class="name">预备基金</span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2>预备大奖说明</h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1>0.2% - 预备大奖</h1>
                            <p>系统重启后开始新的循环后，预备头奖启动，为重启后的市场活力提供生命能量。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/14.png" alt=""></i>
                    <span>1020.17 Matic</span>
                    <span class="name">汽油费</span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2>汽油费说明</h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1>汽油费</h1>
                            <p>迄今为止，Gas 费会消耗成本。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/15.png" alt=""></i>
                    <span>5606</span>
                    <span class="name">成员数</span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2>成员计数说明</h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1>成员数</h1>
                            <p>自系统启动以来的全球注册账户总数。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/16.png" alt=""></i>
                    <span>1187</span>
                    <span class="name">等待计数</span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2>等待计数指令</h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1>等待计数</h1>
                            <p>申请提供帮助（PH 订单）但等待匹配的帐户数量。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/17.png" alt=""></i>
                    <span>0.0 $</span>
                    <span class="name">等待金额</span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2>等待金额说明</h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1>等待金额</h1>
                            <p>等待匹配的 PH 订单总数。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
            <li>
                <a href="javascript:;">
                    <i><img src="/assets/images/18.png" alt=""></i>
                    <span>136280 $</span>
                    <span class="name">今天的提现</span>
                </a>
                <div class="rec-fixed-box">
                    <div class="t">
                        <h2>今天的提款说明</h2>
                        <a href="javascript:;" class="close">&times;</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="b">
                        <div class="box">
                            <h1>今天的提款</h1>
                            <p>今天从 GH 订单支付的总金额。</p>
                        </div>
                        <a href="javascript:;" class="closebtn">关</a>
                        <div class="clearfix"></div>
                    </div>        
                </div>
            </li>
        </ul>        
        <div class="i02-btm">
            <span class="red">所有数据都在上面</span>
            <span>系统已运行：71天1小时8分钟</span>
        </div>
    </div>

    <div class="i03">
        <h2>倒计时</h2>
        <span class="final">最终奖励倒计时</span>
        <div class="times">
            <span class="time hour">00</span>
            <span class="colon">:</span>
            <span class="time minute">00</span>
            <span class="colon">:</span>
            <span class="time second">00</span>
        </div>
        <span class="nostart"><i>倒计时还没有开始</i></span>
    </div>

    <div class="i04">
        <div class="h01">
            <h2>提供帮助</h2>
            <span>“获取”Mavro（做出贡献）</span>
        </div>
        <div class="h01 h02">
            <h2>立即帮助</h2>
            <span>立即做出贡献（竞赛模式）</span>
        </div>
        <div class="h01 h03">
            <h2>得到帮助</h2>
            <span>“兑现”你的Mavro，（提款）</span>
        </div>
        <div class="h01 h04">
            <h2>购买马夫罗</h2>
            <span>“获取”Mavro in（制作银行）</span>
        </div>
    </div>
    
    <div class="rec-fixed-box rec-fixed-box-h02">
        <div class="t">
            <h2>提示</h2>
            <a href="javascript:;" class="close">&times;</a>
            <div class="clearfix"></div>
        </div>
        <div class="b">
            <div class="box">
                <h1>立即捐款说明</h1>
                <p>倒计时开始后，红色按钮PH订单立即匹配总金额的100%</p>
            </div>
            <a href="javascript:;" class="closebtn">关</a>
            <div class="clearfix"></div>
        </div>        
    </div>
    <div class="rec-fixed-box rec-fixed-box-h03">
        <div class="t">
            <h2>添加请求</h2>
            <a href="javascript:;" class="close">&times;</a>
            <div class="clearfix"></div>
        </div>
        <div class="b">
            <div class="box">
                <h1>确认收款人地址</h1>
                <p>0x294e858D26b5e564fD8cA97284372B1F0c7AD421</p>
            </div>
            <div class="btns">
                <a href="javascript:;" class="closebtn">取消</a>
                <a href="javascript:;" class="nextbtn">下一个</a>
            </div>
        </div>        
    </div>
    <div class="rec-fixed-box rec-fixed-box-next">
        <div class="t">
            <h2>添加请求</h2>
            <a href="javascript:;" class="close">&times;</a>
            <div class="clearfix"></div>
        </div>
        <div class="b">
            <div class="box">
                <h1>输入要从您的每个 mavro 帐户中提取的金额</h1>
                <p>提现币种：Mavro-USDT</p>
                <p>可接受的退出频率：10</p>
                <p>可关联解冻数量： 0.00</p>
            </div>
            <a href="javascript:;" class="withdraw">全部撤回</a>
            <table class="tables" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="45%">Mavro钱包(可用)</th>
                        <th width="10%">数量</th>
                        <th width="15%">操作</th>
                        <th width="30%">金额(取款)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>当前，每月增长 30% (Mavro-USDT)</td>
                        <td>0.00</td>
                        <td><input type="button" class="allbtn" value="全部 >" /></td>
                        <td><input type="text" class="money" value="0"><span>USDT</span></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>提款总额</td>
                        <td>0</td>
                        <td></td>
                        <td>USDT</td>
                    </tr>
                </tfoot>
            </table>
            <div class="btns">
                <a href="javascript:;" class="returnbtn">返回>></a>
                <a href="javascript:;" class="submitbtn">提交</a>
            </div>
        </div>        
    </div>
    

    <div class="i05">
        <div class="i05-1">            
            <!-- 已完成的订单请在i05box后面加上 i05-donebox -->
            <div class="i05box">
                <div class="l">
                    <i class="undone"></i>
                    <h2>
                        编号：<br />R222090446550
                    </h2>
                </div>
                <div class="r">
                    <p>你必须付款</p>
                    <p>(请求帮助 R222090446550)</p>
                    <p><span>创建时间：14/11/2022</span></p>
                    <p><b>JY4M44MU></b><strong>1000 USDT</strong></p>
                </div>
            </div>
            <input type="button" value="显示已完成/已取消的订单" class="order-btn" />
        </div>
        <div class="i05-2">
            <!-- 已完成的订单请在i05box后面加上 i05-donebox -->
            <div class="i05box i05-donebox">
                <div class="t">
                    <i class="undone"></i>
                    <h2>请求提供帮助<br />Z22090446550</h2>
                </div>
                <div class="b">
                    <p>参加者：JY4M44MU</p>
                    <p>数量：2000 USDT</p>
                    <p>余额：1000 USDT</p>
                    <p>日期：14/11/2022</p>
                    <p>状态：订单已付</p>
                </div>
            </div>
            <input type="button" value="显示已完成/已取消的请求" class="order-btn" />    
            <div class="clearfix"></div>        
        </div>
    </div>
</div>
<script>        
    function lang11(lang){
            alert(lang)
            setCookie('think_var', lang);
        }
 function setCookie(name,value,seconds) {
    var expires = "";
    if (seconds) {
        var date = new Date();
        date.setTime(date.getTime() + (seconds*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    location.href="/"
}
    $('.i02 ul li a').on('click', function(){
        $('.navbg').css('display','block');
        $(this).next('.rec-fixed-box').css('display','block');
    })
    $('.i04 .h02').on('click', function(){
        $('.rec-fixed-box-h02').css('display','block');
        $('.navbg').css('display','block');
    })
    $('.i04 .h03').on('click', function(){
        $('.rec-fixed-box-h03').css('display','block');
        $('.navbg').css('display','block');
    })
    $('.rec-fixed-box .t .close').on('click', function(){
        $('.rec-fixed-box').css('display','none');
        $('.rec-fixed-box-h02').css('display','none');
        $('.navbg').css('display','none');
    })

    $('.rec-fixed-box .b .closebtn').on('click', function(){
        $('.rec-fixed-box').css('display','none');
        $('.rec-fixed-box-h02').css('display','none');
        $('.navbg').css('display','none');
    })

    $('.rec-fixed-box-h03 .btns .nextbtn').on('click', function(){
        $('.rec-fixed-box-h03').css('display','none');
        $('.rec-fixed-box-next').css('display','block');
    })

    $('.rec-fixed-box-next .btns .returnbtn').on('click', function(){
        $('.rec-fixed-box-h03').css('display','block');
        $('.rec-fixed-box-next').css('display','none');
    });

    $('.rec-fixed-box-next .btns .submitbtn').on('click', function(){
        $('.navbg').css('display','none');
        $('.rec-fixed-box-next').css('display','none');
    });

    $('.i05 .i05-1 .order-btn').on('click', function(){
        if($('.i05 .i05-1 .order-btn').hasClass('hov')){
            $(this).removeClass('hov');
            $(this).prev('.i05box').css('opacity','0')
        }else{
            $(this).addClass('hov');
            $(this).prev('.i05box').css('opacity','1')
        }
    })

    $('.i05 .i05-2 .order-btn').on('click', function(){
        if($('.i05 .i05-2 .order-btn').hasClass('hov')){
            $(this).removeClass('hov');
            $(this).prev('.i05box').css('opacity','0')
        }else{
            $(this).addClass('hov');
            $(this).prev('.i05box').css('opacity','1')
        }
    })
</script>
<script>
    //导航
    $('.header .hr .navicon').on('click', function(){
        if ($('.header .hr .navicon').hasClass('hov')) {
            $('.navbox').css('left','-100%');
            $('.navbg').css('display','none');
            $('.header .hr .navicon').removeClass('hov');
        }else{
            $('.navbox').css('left','0');
            $('.navbg').css('display','block');
            $('.header .hr .navicon').addClass('hov');
        }
    })

    $('.navbox ul li.snav .title_bg').on('click', function(){
        if($(this).hasClass('cur')){
            $(this).removeClass('cur');
            $(this).siblings('.nav01').css('display','none');
        }else{
            $(this).addClass('cur');
            $(this).siblings('.nav01').css('display','block');
        }        
    });

    //banner
    var swiper = new Swiper(".mySwiper", {
        autoplay: true,
    });

    // 倒时计
    //1、获取元素
    //获取当前的日期的后一大
    var year = new Date().getFullYear();
    var month = new Date().getMonth() + 1;
    var day = new Date().getDate()+1;
    // 获取当前时间
    var hour = document.querySelector('.hour');//小时的黑色盒子
    var minute = document.querySelector('.minute');//分钟的黑色盒子
    var second = document.querySelector('.second');//秒数的黑色盒子
    var inputTime = +new Date(year +'-'+ month +'-'+ day + ' 00:00:00');//定义结束的时间
    // console.log(year + '-' + month + '-' + day);    
    var i = 0;
    var timer = null;                     //定义一个全局变量方便清除定时器
    //2、开启定时器    默认未开启状态，需开启时将下面两行注释解开
    // countDown();                         //先调用一次防止第一次刷新页面有空白
    // timer = setInterval(countDown, 1000);         //每隔一秒调用一次
    

    function countDown(time) {
        var nowTime = +new Date();  //返回的是当前时间总的毫秒数
        var times = (inputTime - nowTime) / 1000;     //times 是剩余时间总的秒数
        var h = parseInt(times / 60 / 60 % 24);      //计算小时
        h = h < 10 ? '0' + h : h;
        hour.innerHTML = h;
        var m = parseInt(times / 60 % 60);         //计算分钟
        m = m < 10 ? '0' + m : m;
        minute.innerHTML = m;
        var s = parseInt(times % 60);            //计算秒数
        s = s < 10 ? '0' + s : s;
        second.innerHTML = s;
        console.log(111);
        if (s == '00' && h == '00' && m == '00') {
            second.innerHTML = '00';
            clearInterval(timer);

            setTimeout(function () {         //添加一个定时器，使秒出现 00 时 再弹出提示框
                alert('时间到了');
            }, 1);                           //1ms
        }
    }
</script>
</body>
</html>