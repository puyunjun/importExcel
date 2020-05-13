<?php


namespace App\Http\Controllers\Tools;

use App\Jobs\DealData;
use App\Models\Reservation;
use App\Servers\PhpSpreadSheet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DealDatasController extends Controller
{

    public static $inData =[];
    //首页
    public function index(Request $request)
    {
        $this->takeCopyData();

        /*$this->addStudentStu();

        $this->addStudentTeacherData();*/

        /*$jsonData = file_get_contents('noImportDeviceRecord.json');
        $jsonAllData = file_get_contents('noImportRecord.json');

        dd(count(json_decode($jsonAllData,true)),json_decode($jsonData,true));*/
        $file = $request->file('datas');

        if($file){
            //读取excel数据
            $spread = (new PhpSpreadSheet())->getIntence($file->getRealPath());

            $data = $spread->getExcelData();


            $model = new Reservation();

            $da = $model->getCombineData($data);

           /* $this->dealOldData($da);
            return '';*/
            //处理数据
            $dealRes = array_chunk($da,  100);
            foreach ($dealRes as $index=>$item){
                /*if($index > 20){
                    continue;
                }*/
                DealData::dispatch($item);
                //$this->dealOldData($item);
                //$res = $model->dealData($item);
            }
            dd(self::$inData);
            //生成json文件
            //touch('noImportRecord.json');
            /*touch('noImportStuRecord.json');
            touch('noImportDeviceRecord.json');
            touch('noImportTeachRecord.json');
            touch('noImportOperRecord.json');
            file_put_contents('noImportRecord.json',json_encode(Reservation::$hasNoRecordData));
            file_put_contents('noImportStuRecord.json',json_encode(Reservation::$hasNoRecordStu));
            file_put_contents('noImportDeviceRecord.json',json_encode(Reservation::$hasNoRecordDevice));
            file_put_contents('noImportTeachRecord.json',json_encode(Reservation::$hasNoRecordTeach));
            file_put_contents('noImportOperRecord.json',json_encode(Reservation::$hasNoRecordOper));*/

            Log::info(Reservation::$hasNoRecordData);
            dd(Reservation::$hasNoRecordStu,Reservation::$hasNoRecordDevice,Reservation::$hasNoRecordTeach,Reservation::$hasNoRecordOper);
            dd(2);
        }
        return view('tools.upload_index', []);
    }


    //处理数据
    //最笨的方法，处理8000多次
    public function dealOldData($data = [])
    {
        $noDevice = [];
        $insertData = [];
        //分成100条的数据块

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
                'device_id'=>$deviceId,
                'student_id'=>$studentId,
                'teacher_id'=>$teacherId,
                'opera_id'=>$operateId,
                'sample_num'=>$item['numbers'],
                'begin_time'=>strtotime($item['startTime']) ? strtotime($item['startTime']) : 0,
                'finish_time'=>strtotime($item['endTIme']) ? strtotime($item['startTime']) : 0,
                'amount'=>$item['consumptionAmount'],
                'create_time'=>strtotime($item['startTime']) ? strtotime($item['startTime']) : 0,
                'remark'=>$item['remark'],
                'status'=>1
            ];

        }
        //dd(array_unique($noDevice));

    }
    //表格数据添加
    public function addStudentTeacherData()
    {
        //
        $sql = "SELECT * FROM dj_user_all
                    where  school_number  in
                (SELECT `no` FROM cq_student ) AND teacher_id = 0 AND role >1
                ";
        $res = DB::select(DB::raw($sql));


        //将没有添加到student 表的导师添加到数据库中
        $insertTeacherData = [];
        foreach ($res as $value){
            //查找当前老师对应的学院id

            $sql = "update student set `type` = 1 where `no` = '$value->school_number' and `name` = '$value->user_name';";
            logger($sql);
            DB::table('student')->where([
                ['no', '=', $value->school_number],
                ['name', '=', $value->user_name]
            ])->update(['type'=>1]);
            $schoolId = DB::table('school')->where('name',$value->school_name)->value('id');
            $insertTeacherData[]=[
                'no'=>substr($value->school_number,0, 16),
                'name'=>$value->user_name,
                'mobile'=>$value->mobile,
                'school_id'=>$schoolId,
                'type'=>1,
                'email'=>$value->email
            ];
        }
        //DB::table('student')->insert($insertTeacherData);
        dd($insertTeacherData);
    }

    public function addStudentStu()
    {
        $sql = "SELECT * FROM dj_user_student_view where  school_number not in (SELECT `no` FROM cq_student )";

        $res = DB::select(DB::raw($sql));

        $insertData = [];
        foreach ($res as $item){
            //查找当前对应学生导师工号对应的student表id
            $teachNum = $item->teacherNumber;

            $teacherId = DB::table('student')->where('no',$teachNum)->value('id');

            $schoolId = DB::table('school')->where('name',$item->school_name)->value('id');
            $insertData[] = [
                'no'=>substr($item->school_number,0, 16),
                'name'=>$item->user_name,
                'mobile'=>$item->mobile,
                'teacher_id'=>$teacherId ? $teacherId : 0,
                'school_id'=>$schoolId,
                'email'=>$item->email
            ];
        }

        DB::table('student')->insert($insertData);
    }

    //处理重复数据
    public function takeCopyData()
    {
        $sql = "SELECT COUNT(id) as num_id,`name`,`no` from student  group  BY `name`,`no` HAVING num_id > 1";

        $res = DB::select(DB::raw($sql));

        $schooSql  = DB::table('school')->where('pid',2)->get()->toArray();

        $cq = array_column($schooSql,'id');
        $updateData = [];
        foreach ($res as $item){
            //查询当前学生。学号匹配的记录
            $student = DB::table('student')->where([
                ['name','=',$item->name],
                ['no','=',$item->no]
            ])->get()->toArray();
            ;
            foreach ($student as $value){
                if(in_array($value->school_id,$cq)){
                    //是重大的学生,记录要修改的地方
                    if($value->teacher_id){
                        $updateData[] = [
                            'school_id'=>$value->school_id,
                            'teacher_id'=>$value->teacher_id,
                            'name'=>$value->name,
                            'no'=>$value->no
                        ];
                    }

                }
            }
            //dd($student);
        }
        foreach ($updateData as $value){

            $sqlNum = "UPDATE student SET school_id = ".$value['school_id'].",teacher_id = ".$value['teacher_id']." WHERE `name` = '".$value['name']."' AND `no` = '".$value['no']."';";
            Log::info($sqlNum);

        }
    }
}
