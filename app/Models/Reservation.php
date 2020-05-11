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

        //分别取出对应名字的id，角色不同，匹配条件不同

        //使用人id信息
        $userIdInfo = Student::getUserId($userInfo, Student::STUDENT_ROLE);

        $tutorInfo = array_unique(array_column($data, 'tutor'));  //导师信息

        $operator = array_unique(array_column($data, 'operator')); //操作者信息

        //导师及操作员id信息
        $teacherIdInfo = Student::getUserId(array_unique(array_merge($tutorInfo, $operator)), Student::TEACHER_ROLE);

        $compareData = [
            'deviceInfo'=>$deviceData,
            'studentInfo'=>$userIdInfo,
            'teacherInfo'=>$teacherIdInfo,
        ];

        return self::compareUserToId($data, $compareData);

    }

    /**
     * @param array $data
     * @param array $compareData
     * @return array
     */
    public static function compareUserToId($data = [], $compareData = [])
    {
        $device = array_column($compareData['deviceInfo'], 'remark', 'id');

        $userStudent = array_column($compareData['studentInfo'], 'name', 'id');

        $userTeacher = array_column($compareData['teacherInfo'], 'name', 'id');

        foreach ($data as $key=>$item){
            //替换匹配到的id值
            //设备名称
            $keySearDev = array_search($device, $item['equipmentName']);
            if($keySearDev){
                $data[$key]['equipmentName'] = $device[$keySearDev];
            }

            //学生姓名
            $keySearStuName = array_search($userStudent, $item['user']);
            if($keySearStuName){
                $data[$key]['user'] = $userStudent[$keySearStuName];
            }

            //导师姓名
            $keySearTeachName = array_search($userTeacher, $item['tutor']);
            if($keySearTeachName){
                $data[$key]['tutor'] = $userTeacher[$keySearTeachName];
            }

            //操作员姓名
            $keySearOperName = array_search($userTeacher, $item['operator']);
            if($keySearOperName){
                $data[$key]['operator'] = $userTeacher[$keySearOperName];
            }
        }
        return $data;
    }
}
