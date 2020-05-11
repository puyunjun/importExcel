<?php


namespace App\Http\Controllers\Tools;

use App\Models\Reservation;
use App\Servers\PhpSpreadSheet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DealDatasController extends Controller
{

    //首页
    public function index(Request $request)
    {
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
                if($index > 0){
                    continue;
                }
                $model->dealData($item);
            }
            dd($da);
        }
        return view('tools.upload_index', []);
    }


    //处理数据
    public function dealOldData($sourcePath = '')
    {

    }
}
