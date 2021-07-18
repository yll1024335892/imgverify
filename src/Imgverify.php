<?php

namespace Ayi\imgverify;
class Imgverify
{
    static public $instance = null;
    private $errorNum; //同一图片的校验次数,默认为2
    private $valid_time;//验证码过期的时间(s),默认600s
    private $x_error_distance;//误差距离,单位为px,默认左右4px的容差距离

    private function __construct($config = null)
    {
        session_start();
        $this->errorNum = empty($config['error_num']) ? 2 : intval($config['error_num']);
        $this->valid_time = empty($config['valid_time']) ? 600 : intval($config['valid_time']);
        $this->x_error_distance = empty($config['error_distance']) ? 4 : floatval($config['error_distance']);
    }

    public static function getInstance($config = null)
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function setConfig($bgImg = [], $drawImg = [])
    {
        if (count($bgImg) < 1 || count($drawImg) < 1) {
            return false;
        }
        $_SESSION['imgverify_x'] = rand(130, 550);
        $_SESSION['imgverify_y'] = rand(50, 260);
        $_SESSION['imgverify_bgImg'] = $bgImg[array_rand($bgImg)];//原图要求是 1920x1080这个比例的，679x382【图片避免用白色背景的】
        $_SESSION['imgverify_dragImgn'] = $drawImg[array_rand($drawImg)];
        $_SESSION['imgverify_opacity'] = rand(30, 80);//原图上空缺的位置的透明度【这个可以增加被破解的记录】
        $_SESSION['imgverify_time'] = time() + $this->valid_time;//设置失效时间 600=10分钟
        $_SESSION['imgverify_error_num'] = $this->errorNum;//验证图形验证码时，错误次数不能超过2次
        return true;
    }

    /**
     * @param $type  bgImg 生成背景 | dragImg 生成拖动的图
     * @return string
     */
    public function getImg($type)
    {
        //获取 SESSION 信息
        $x = $_SESSION['imgverify_x'];//设置验证码
        $y = $_SESSION['imgverify_y'];//设置验证码
        $img = $_SESSION['imgverify_bgImg'];
        $moban = $_SESSION['imgverify_dragImgn'];
        $opacity = $_SESSION['imgverify_opacity'];
        if (!(isset($x) && isset($y) && isset($img) && isset($moban) && isset($opacity))) {
            return json_encode(['status' => "err", "data" => "图形滑动验证码尚未生成"]);
        }
        //创建源图的实例
        $src = imagecreatefromstring(file_get_contents($img));
        //新建一个真彩色图像【尺寸 = 90x90】【目前是不透明的】
        $res_image = imagecreatetruecolor(90, 90);
        //创建透明背景色，主要127参数，其他可以0-255，因为任何颜色的透明都是透明
        $transparent = imagecolorallocatealpha($res_image, 255, 255, 255, 127);
        //指定颜色为透明（做了移除测试，发现没问题）
        imagecolortransparent($res_image, $transparent);
        //填充图片颜色【填充会将相同颜色值的进行替换】
        imagefill($res_image, 0, 0, $transparent);//左边的半圆

        //实现两个内凹槽【填补上纯黑色】
        $tempImg = imagecreatefrompng($moban);//加载模板图
        for ($i = 0; $i < 90; $i++) {// 遍历图片的像素点
            for ($j = 0; $j < 90; $j++) {
                if (imagecolorat($tempImg, $i, $j) !== 0) {// 获取模板上某个点的色值【取得某像素的颜色索引值】【0 = 黑色】
                    $rgb = imagecolorat($src, $x + $i, $y + $j);// 对应原图上的点
                    imagesetpixel($res_image, $i, $j, $rgb);// 移动到新的图像资源上
                }
            }
        }
        if ($type == 'bgImg') {
            //制作一个半透明白色蒙版
            $mengban = imagecreatetruecolor(90, 90);
            //先让蒙版变成透明的
            //指定颜色为透明（做了移除测试，发现没问题）
            imagecolortransparent($mengban, $transparent);
            //填充图片颜色【填充会将相同颜色值的进行替换】
            imagefill($mengban, 0, 0, $transparent);
            $huise = imagecolorallocatealpha($res_image, 255, 255, 255, $opacity);
            for ($i = 0; $i < 90; $i++) {// 遍历图片的像素点
                for ($j = 0; $j < 90; $j++) {
                    $rgb = imagecolorat($res_image, $i, $j); // 获取模板上某个点的色值【取得某像素的颜色索引值】
                    if ($rgb !== 2147483647) {// 获取模板上某个点的色值【取得某像素的颜色索引值】【0 = 黑色】
                        imagesetpixel($mengban, $i, $j, $huise);// 对应点上画上黑色
                    }
                }
            }
            //把修改后的图片，放回原本的位置
            imagecopyresampled(
                $src,//裁剪后的存放图片资源
                $res_image,//裁剪的原图资源
                $x, $y,//存放的图片，开始存放的位置
                0, 0,//开始裁剪原图的位置
                90, 90,//存放的原图宽高
                90, 90//裁剪的原图宽高
            );
            //把蒙版添加到原图上去
            imagecopyresampled(
                $src,//裁剪后的存放图片资源
                $mengban,//裁剪的原图资源
                $x + 1, $y + 1,//存放的图片，开始存放的位置
                0, 0,//开始裁剪原图的位置
                90 - 2, 90 - 2,//存放的原图宽高
                90, 90//裁剪的原图宽高
            );
            ob_start();
            imagejpeg($src);
            $image_data = ob_get_contents();
            ob_end_clean();
            $image_data_base64 = "data:image/png;base64," . base64_encode($image_data);
            return json_encode(['status' => "ok", "data" => $image_data_base64]);
        }
        if ($type == 'dragImg') {
            //补上白色边框
            $tempImg = imagecreatefrompng($moban . '.png');//加载模板图
            $white = imagecolorallocatealpha($res_image, 255, 255, 255, 1);
            for ($i = 0; $i < 90; $i++) {// 遍历图片的像素点
                for ($j = 0; $j < 90; $j++) {
                    if (imagecolorat($tempImg, $i, $j) === 0) {// 获取模板上某个点的色值【取得某像素的颜色索引值】【0 = 黑色】
                        imagesetpixel($res_image, $i, $j, $white);// 对应点上画上黑色
                    }
                }
            }
            //创建一个90x382宽高 且 透明的图片
            $res_image2 = imagecreatetruecolor(90, 382);
            //指定颜色为透明（做了移除测试，发现没问题）
            imagecolortransparent($res_image2, $transparent);
            //填充图片颜色【填充会将相同颜色值的进行替换】
            imagefill($res_image2, 0, 0, $transparent);//左边的半圆
            //把裁剪的图片，移到新图片上
            imagecopyresampled(
                $res_image2,//裁剪后的存放图片资源
                $res_image,//裁剪的原图资源
                0, $y,//存放的图片，开始存放的位置
                0, 0,//开始裁剪原图的位置
                90, 90,//存放的原图宽高
                90, 90//裁剪的原图宽高
            );
            ob_start();
            imagepng($res_image2);
            $image_data = ob_get_contents();
            ob_end_clean();
            $image_data_base64 = "data:image/png;base64," . base64_encode($image_data);
            return json_encode(['status' => "ok", "data" => $image_data_base64]);
        }
    }

    /**
     * 校验
     */
    public function verify()
    {
        if (!(isset($_SESSION['imgverify_x']) && isset($_SESSION['imgverify_error_num']) && isset($_SESSION['imgverify_time']))) {
            return json_encode(['status' => "err", "data" => "获取图形验证码失败"]);
        }
        if ($_SESSION['imgverify_time'] <= time()) {
            return json_encode(['status' => "err", "data" => "验证码已过期"]);
        }
        if (preg_match('/^[0-9]{1,4}$/', $_GET['x']) === 0) {
            return json_encode(['status' => "err", "data" => "位置的字符类型有误"]);
        }
        if ($_GET['x'] <= $_SESSION['imgverify_x'] + 4 && $_GET['x'] >= $_SESSION['imgverify_x'] - 4) {//左右两边都有4px的包容度
            return json_encode(['status' => "ok", "data" => "验证成功"]);
        } else {
            $_SESSION['imgverify_error_num'] -= 1;
            if ($_SESSION['imgverify_error_num'] == 0) {
                return json_encode(['status' => "err", "data" => "验证码错误次数过多，请重新获取"]);
            }
            return json_encode(['status' => "err", "data" => "图形滑块验证码位置有误"]);
        }
    }
}

?>