<?php


namespace App\Http\Controllers\Tools;

use App\Models\Reservation;
use App\Servers\PhpSpreadSheet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DealDatasController extends Controller
{

    //首页
    public function index(Request $request)
    {

        //$this->addStudentTeacherData();
        $this->addStudentStu();
        dd(1);
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

            //处理数据
            $dealRes = array_chunk($da,  100);
            foreach ($dealRes as $index=>$item){
                /*if($index > 20){
                    continue;
                }*/
                $res = $model->dealData($item);
            }

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
    //最笨的方法，处理8000多次,或者用任务队列跑
    public function dealOldData($data = [])
    {
        foreach ($data as $item){
            //学生姓名
            $studentName = $item['user'];

            //先匹配学生姓名

            //todo  首先使用使用者名称和导师名称到 dj_user_student_view视图里面去匹配，
            //todo 取出学生id(使用者id)，并找到导师工号，再去student表里面找导师id，若无法取出，则取student里面的学生id.导师同样取student里面的id
            //导师姓名
            $tutor = $item['tutor'];

        }
    }
    //表格数据添加
    public function addStudentTeacherData()
    {
        //
        $sql = "SELECT * FROM dj_user_all
                    where  school_number not  in
                (SELECT `no` FROM cq_student ) AND teacher_id = 0 AND role >1
                ";
        $res = DB::select(DB::raw($sql));
        //将没有添加到student 表的导师添加到数据库中
        $insertTeacherData = [];
        foreach ($res as $value){
            //查找当前老师对应的学院id
            $schoolId = DB::table('school')->where('name',$value->school_name)->value('id');
            $insertTeacherData[]=[
                'no'=>$value->school_number,
                'name'=>$value->user_name,
                'mobile'=>$value->mobile,
                'school_id'=>$schoolId,
                'email'=>$value->email
            ];
        }
        DB::table('student')->insert($insertTeacherData);
        //dd($insertTeacherData);
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
                'no'=>$item->school_number,
                'name'=>$item->user_name,
                'mobile'=>$item->mobile,
                'teacher_id'=>$teacherId ? $teacherId : 0,
                'school_id'=>$schoolId,
                'email'=>$item->email
            ];
        }
        dd($insertData);
    }
}
