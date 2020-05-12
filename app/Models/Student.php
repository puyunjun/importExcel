<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    //
    protected $table = 'student';

    public $guarded = [];

    public $timestamps = false;

    //导师角色
    const TEACHER_ROLE = 2;

    //学生角色
    const STUDENT_ROLE = 1;

    /**
     * 获取姓名及角色对应的id
     * @param array $names
     * @param int $roleType
     * @return mixed
     */
    public static function getUserId($names = [], $roleType = 1)
    {
        if(!$roleType){
            $where = [];
        }else{
            $where = [
                ['A.role', '=', $roleType]
            ];
        }
       return DB::table('dj_user as A')
           ->whereIn('A.user_name', $names)
           ->where($where)
           ->leftJoin('dj_user as B','A.teacher_id', '=', 'B.user_id')
           ->select(['A.*','B.user_name as teacher_name'])
           ->get()
           ->toArray();

    }
}
