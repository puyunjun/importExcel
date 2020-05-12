<?php


namespace App\Traits;


Trait ExcelImport
{

    public $excelName = [
        'appointmentDate', //预约日期
        'equipmentName',   //设备名称
        'user',       //使用人
        'tutor',      //导师
        'operator',   //操作者
        'startTime',  //上机开始时间
        'endTIme',    //上机结束时间
        'totalTime',  //计时分钟数（00:00:00）
        'numbers',    //样品数
        'price',      //单价
        'consumptionAmount',     //消费金额
        'remark',     //备注
        1,1,1   //匹配数组列数
        ];

    public function combineData($excelData = [])
    {
        $data = [];
        foreach ($excelData as $key=>$value){

            if($key == 0){
                continue;
            }
            $num = count($this->excelName) - count($value);
            if($num > 0){
                for ($i = 0; $i<$num; $i++){
                    array_push($value,1);
                }
            }
            if($num < 0){
                for ($i = 0; $i<abs($num); $i++){
                    array_push($this->excelName,1);
                }
            }
            $data[] = array_combine($this->excelName, $value);
        }
        return $data;
    }
}
