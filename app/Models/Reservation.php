<?php

namespace App\Models;

use App\Traits\ExcelImport;
use Illuminate\Database\Eloquent\Model;
use App\Models\Device;

class Reservation extends Model
{
    //
    protected $table = 'cqudj_reservation';

    public $guarded = [];

    public $timestamps = false;

    //导入的设备名称信息
    public static $importDeviceEquipment;


    use ExcelImport;

    public function getCombineData($excelData = [])
    {
        return $this->combineData($excelData);
    }


    //处理数据
    public function dealData($data = [])
    {

        static::$importDeviceEquipment = array_unique(array_column($data, 'equipmentName'));

        //设备名称
        $deviceData = Device::replaceDeviceNameToId(static::$importDeviceEquipment);
        //TODO 对比获取其id信息

        //人员信息

        $userInfo = array_unique(array_column($data,'user'));  //使用人信息

        $tutorInfo = array_unique(array_column($data, 'tutor'));  //导师信息

        $operator = array_unique(array_column($data, 'operator')); //操作者信息





    }
}
