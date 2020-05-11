<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<form method="post" enctype="multipart/form-data" action="{{route('tools.upload-index')}}">
    @csrf
    <input type="file" name="datas" />文件上传
    <button type="submit">提交</button>
</form>

</body>
</html>
