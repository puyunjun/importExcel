<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //
    protected $table = 'student';

    public $guarded = [];

    public $timestamps = false;

    //老师角色
    const TEACHER_ROLE = 1;

    //学生角色
    const STUDENT_ROLE = 2;

    /**
     * 获取姓名及角色对应的id
     * @param array $names
     * @param int $roleType
     * @return mixed
     */
    public static function getUserId($names = [], $roleType = 1)
    {
       return Student::whereIn('name', $names)
           ->where('type', $roleType)
           ->select(['id','name'])
           ->get()
           ->toArray();

    }
}
