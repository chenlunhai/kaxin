<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');

    }

    protected function execute(Input $input, Output $output)
    {
       // $UserTeam = new \app\common\model\UserTeam(); 
        // return $UserTeam->handleBuild();
        // return $UserTeam->dataFunc();
        // return $UserTeam->dataFuncSecond();
        // return $UserTeam->handleBuildSecond();
        // return $UserTeam->handleBuildTreew();
        // return $UserTeam->countFunc();
        // return $UserTeam->recoverScoreSum();
        // return $UserTeam->recoverPersonAB();

        // $userSubmeter = new \app\api\model\wanlshop\UserSubmeter();
        // return $userSubmeter->handleactive();
       $dosth = new \app\common\controller\Dosth();
        $dosth->dorelease();
        $dosth->updateday();
       
    }

}