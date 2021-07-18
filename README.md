

## Usage

- 引入

```php
require __DIR__ .'/vendor/autoload.php';
```
- 引入

```php
use ayi\imgverify\Imgverify;
```
- 实例化

```php
//没有参数实例
$obj=Imgverify::getInstance();
//带参数实例
//error_num同一图片的校验次数,默认为2
//valid_time验证码过期的时间(s),默认600s
//error_distance误差距离,单位为px,默认左右4px的容差距离
$config=["error_num"=>'',"valid_time"=>'',"error_distance"=>""];
$obj=Imgverify::getInstance($config);
```

- 背景图片(1920x1080这个比例的,现使用679px*382px且图片避免用白色背景的)和拖动图片(90px*90px)

```
在https://github.com/yll1024335892/imgverify的image中的分支中
```

- 获取图片的数据举例

```
$bg = [$_SERVER['DOCUMENT_ROOT'] . '/images/bg/1.jpg', $_SERVER['DOCUMENT_ROOT'] . '/images/bg/2.jpg', $_SERVER['DOCUMENT_ROOT'] . '/images/bg/3.jpg', $_SERVER['DOCUMENT_ROOT'] . '/images/bg/4.jpg'];
$d = [$_SERVER['DOCUMENT_ROOT'] . "/images/dr/1.png"];
$res1 = $obj->setConfig($bg, $d);
$imgData = $obj->getImg("bgImg"); //背景图片
echo "<img src='" . json_decode($imgData)->data . "' />";
$imgData = $obj->getImg("dragImg"); //拖动的图片
echo "<img src='" . json_decode($imgData)->data . "' />";
```

- 校验

```
$obj->verify(x的距离)
```


## License

MIT
