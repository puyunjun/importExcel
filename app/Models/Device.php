<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    //

    protected $table = 'device';

    public $guarded = [];



    //匹配设备信息
    public static function  replaceDeviceNameToId($combineDeviceData = [])
    {
        //获取设备名称对应的id
        /**
         * array:8 [▼
        0 => "德国耐驰449F3"                   +
        1 => "A区国产电解双喷(MTP-1A)"         +
        2 => "德国耐驰449C"                    +
        3 => "瑞士METTLER 1100LF"              +
        6 => "德国耐驰402C"
        10 => "JEOL JSM-7800F FEG SEM（2）"  +
        59 => "Zeiss Auriga SEM-FIB 双束电镜(SEM扫描功能)"
        84 => "KYKY SBC-12喷金"
        ]
         */
        return Device::whereIn('remark',$combineDeviceData)->get()->toArray();

    }

    /**
     * 比对
     * @param array $device
     */
    protected function compareEquipmentId($device = [])
    {

    }

}
