<?php

namespace App\Models;

use App\Traits\ExcelImport;
use Illuminate\Database\Eloquent\Model;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function Matrix\trace;

class Reservation extends Model
{
    //
    protected $table = 'cqudj_reservation';

    public $guarded = [];

    public $timestamps = false;

    //导入的设备名称信息
    public static $importDeviceEquipment;


    //没有匹配到的设备记录
    public static $hasNoRecordDevice;

    //没有匹配到的学生记录
    public static $hasNoRecordStu;

    //没有匹配到的导师记录
    public static $hasNoRecordTeach;

    //没有匹配到的操作员记录
    public static $hasNoRecordOper;

    //没有匹配到的数据
    public static $hasNoRecordData;

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

        self::insertStudentExcel($userIdInfo, $userInfo,0);


        $tutorInfo = array_unique(array_column($data, 'tutor'));  //导师信息

        $operator = array_unique(array_column($data, 'operator')); //操作者信息

        //导师id信息
        $teacherIdInfo = Student::getUserId($tutorInfo, Student::TEACHER_ROLE);

        self::insertStudentExcel($teacherIdInfo, $tutorInfo,1);

        //操作员id信息,学生id
        $operatorIdInfo = Student::getUserId($operator, 0);

        self::insertStudentExcel($operatorIdInfo, $operator,0);

        $compareData = [
            'deviceInfo'=>$deviceData,
            'studentInfo'=>$userIdInfo,
            'teacherInfo'=>$teacherIdInfo,
            'operatorInfo'=>$operatorIdInfo,
        ];

        //return self::compareUserToId($data, $compareData);

    }

    /**
     * @param array $data
     * @param array $compareData
     * @return array
     */
    public static function compareUserToId($data = [], $compareData = [])
    {
        $device = array_column($compareData['deviceInfo'], 'remark', 'id');

        $userStudent = array_column($compareData['studentInfo'], 'user_name', 'user_id');

        $userTeacher = array_column($compareData['teacherInfo'], 'user_name', 'user_id');

        $userOperator = array_column($compareData['operatorInfo'], 'user_name', 'user_id');

        $originExcelData = $data;
        $insertReservationData = [];

        $updatePromoIdData = [];
        foreach ($data as $key=>$item){
            $data[$key]['isMatching'] = true;  //完全匹配标识
            //替换匹配到的id值
            //设备名称
            $keySearDev = array_search($item['equipmentName'], $device);
            if($keySearDev){
                $data[$key]['equipmentName'] = $keySearDev;
            }else{
                $data[$key]['isMatching'] = false;
                $originExcelData[$key]['reason_d'] = '没有匹配到设备名称';
                self::$hasNoRecordDevice[] = $originExcelData[$key];
            }

            //学生姓名
            $keySearStuName = array_search($item['user'], $userStudent);
            if($keySearStuName){
                $data[$key]['user'] = $keySearStuName;
            }else{
                $data[$key]['isMatching'] = false;
                $originExcelData[$key]['reason_s'] = '没有匹配到学生名称';
                self::$hasNoRecordStu[] = $originExcelData[$key];
            }

            //导师姓名
            $keySearTeachName = array_search($item['tutor'], $userTeacher);
            if($keySearTeachName){
                $data[$key]['tutor'] = $keySearTeachName;
            }else{
                $data[$key]['isMatching'] = false;
                $originExcelData[$key]['reason_t'] = '没有匹配到导师名称';
                self::$hasNoRecordTeach[] = $originExcelData[$key];
            }

            //操作员姓名
            $keySearOperName = array_search($item['operator'], $userOperator);
            if($keySearOperName){
                $data[$key]['operator'] = $keySearOperName;
            }else{
                $data[$key]['isMatching'] = false;
                $originExcelData[$key]['reason_t'] = '没有匹配到操作员名称';
                self::$hasNoRecordOper[] = $originExcelData[$key];
            }

            //返回批量添数据
            if($data[$key]['isMatching'] == true){
                $insertReservationData[] = [
                    'device_id'=>$data[$key]['equipmentName'],
                    'student_id'=>$data[$key]['user'],
                    'teacher_id'=>$data[$key]['tutor'],
                    'opera_id'=>$data[$key]['operator'],
                    'sample_num'=>$data[$key]['numbers'],
                    'begin_time'=>strtotime($data[$key]['startTime']) ? strtotime($data[$key]['startTime']) : 0,
                    'finish_time'=>strtotime($data[$key]['endTIme']) ? strtotime($data[$key]['startTime']) : 0,
                    'amount'=>$data[$key]['consumptionAmount'],
                    'create_time'=>time(),
                    'remark'=>$data[$key]['remark'],
                    'status'=>1
                ];

                $updatePromoIdData[] = [
                    'id'=>$data[$key]['tutor'],
                    'promo_id'=>$data[$key]['consumptionAmount'],
                ];
                //$res  = self::updateBatch('student',$updatePromoIdData);

            }else{
                //记录没有匹配到的数据
                static::$hasNoRecordData[] = $originExcelData[$key];
            }
            //无法判断数据 研究项目id,  计划id, 授权时间,说明，样品类型
        }

        return ['originDealData'=>$data,'insertReservationData'=>$insertReservationData];
    }

    public static function updateBatch($tableName = "", $multipleData = array()){

        if( $tableName && !empty($multipleData) ) {

            // column or fields to update
            $updateColumn = array_keys($multipleData[0]);

            //找出主键的位置
            $idPoIndex = array_search('id',$updateColumn);

            $referenceColumn = $updateColumn[$idPoIndex]; //e.g id

            unset($updateColumn[$idPoIndex]);
            $whereIn = "";

            $q = "UPDATE ".$tableName." SET ";
            foreach ( $updateColumn as $uColumn ) {
                $q .=  $uColumn." = $uColumn - CASE ";

                foreach( $multipleData as $data ) {
                    $q .= "WHEN ".$referenceColumn." = ".$data[$referenceColumn]." THEN \"".$data[$uColumn]."\" ";
                }
                $q .= "ELSE ".$uColumn." END, ";
            }
            foreach( $multipleData as $data ) {
                $whereIn .= "\"".$data[$referenceColumn]."\", ";
            }
            $q = rtrim($q, ", ")." WHERE ".$referenceColumn." IN (".  rtrim($whereIn, ', ').")";

            // Update
            return $q;
            //return DB::update(DB::raw($q));

        } else {
            return false;
        }
    }

    protected static function grepPrecial($arr = [])
    {
        foreach ($arr as $k=>$item){

            preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $item, $matches);

            $arr[$k] = join('', $matches[0]);

        }


        return $arr;
    }

    protected static function insertStudentExcel($userIdInfo,$userInfo, $type=0)
    {
        //插入信息
        $grepStudent = array_column($userIdInfo,'user_name');


        foreach ($userIdInfo as $item){
            //获取当前school_id
            $schoolId = DB::table('school')->where('name',$item->school_name)->value('id');
            $insertStu[] = [
                'name'=>$item->user_name,
                'type'=>$type,//学生
                'mobile'=>$item->mobile,
                'school_id'=>$schoolId,
                'email'=>$item->email,
                'no'=>$item->school_number
            ];
        }
        //插入
        DB::table('student_excel')->insertOrIgnore($insertStu ?? []);
        //没有匹配到的
        $diffStudent = array_diff($userInfo,array_unique($grepStudent));
        foreach ($diffStudent as $k=>$value){
            $diffInsertData[] = [
                'name'=>$value
            ];
        }
        DB::table('student_excel')->insertOrIgnore($diffInsertData ?? []);
    }


    public static function dealOldData($data = [])
    {
        $noDevice = [];
        $insertData = [];
        //分成100条的数据块
        $updatePromoIdData = [];
        foreach ($data as $k=>$item){
            //匹配设备id
            $deviceId = DB::table('device')->where('remark','like','%'.$item['equipmentName'].'%')->value('id');

            if(!$deviceId){

                $noDevice[] = $item['equipmentName'];
            }
            //学生姓名
            $studentName = $item['user'];

            $teacherName = $item['tutor'];
            //先匹配学生姓名

            // 首先使用使用者名称和导师名称到 dj_user_student_view视图里面去匹配，
            // 并找到导师工号，再去student表里面找导师id，若无法取出，则取student里面的学生id.导师同样取student里面的id
            //导师姓名
            $sql = "select * from dj_user_student_view where `user_name` = '$studentName' and teacherName = '$teacherName' limit 1";

            $djUserStudent = DB::select(DB::raw($sql));
            if($djUserStudent[0] ?? false){
                //通过学生学号和导师工号找出对应的id
                $studentIdInfo = DB::table('student')
                    ->where([
                        ['no', '=', $djUserStudent[0]->school_number],
                        ['name', '=', $djUserStudent[0]->user_name]
                    ])
                    ->first();

                $teacherIdInfo = DB::table('cq_student')
                    ->where([
                        ['no', '=', $djUserStudent[0]->teacherNumber],
                        ['name', '=', $djUserStudent[0]->teacherName]
                    ])
                    ->first();
            }

            //学生id
            $studentId = $studentIdInfo->id ?? 0;
            //导师id
            $teacherId = $teacherIdInfo->id ?? 0;

            //获取操作员id
            $operateIdInfo = DB::table('cq_student')->where('name',$item['operator'])->first();

            $operateId = $operateIdInfo ? $operateIdInfo->id : 0;

            $insertData[] = [
                'device_id'=>$deviceId ? $deviceId : 0,
                'student_id'=>$studentId,
                'teacher_id'=>$teacherId,
                'opera_id'=>$operateId,
                'sample_num'=>$item['numbers'] ? intval($item['numbers']) : 0,
                'begin_time'=>strtotime($item['startTime']) ? strtotime($item['startTime']) : 0,
                'finish_time'=>strtotime($item['endTIme']) ? strtotime($item['startTime']) : 0,
                'amount'=>$item['consumptionAmount'] ? intval($item['consumptionAmount']) : 0,
                'create_time'=>strtotime($item['startTime']) ? strtotime($item['startTime']) : 0,
                'remark'=>$item['remark'] ?? '',
                'status'=>1
            ];
            if($teacherId){
                $updatePromoIdData[] = [
                    'id'=>$teacherId,
                    'promo_id'=>$item['consumptionAmount'],
                ];
            }

        }

        //Log::info($insertData);
        //添加到记录中
        DB::beginTransaction();
        try{
            Reservation::insert($insertData);
            //$res = Reservation::updateBatch('student',$updatePromoIdData);
            //Log::info($res);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($e->getMessage());
        }


    }
}
