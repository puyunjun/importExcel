<?php


namespace App\Servers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
class PhpSpreadSheet
{

    public $spread;

    //获取spreadSheet实例化

    /**
     * @param string $sourcePath excel文件路径
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public  function getIntence($sourcePath = '')
    {
        $this->spread =  IOFactory::load($sourcePath);

        return  $this;
    }

    //获取excel数据
    public function getExcelData()
    {
        return $this->spread->getActiveSheet()->toArray();

    }
}
