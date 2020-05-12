<?php


namespace App\Http\Controllers\Tools;

use App\Models\Reservation;
use App\Servers\PhpSpreadSheet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DealDatasController extends Controller
{

    //首页
    public function index(Request $request)
    {

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
    public function dealOldData($data = [])
    {
        foreach ($data as $item){
            //学生姓名
            $studentName = $item['user'];

            //导师姓名
            $tutor = $item['tutor'];

        }
    }
}
