<h1 align="center"> mixin </h1>

<p align="center"> 🔥🔥🔥🔥火热开发中🔥🔥🔥🔥 </p>

## Installing

```shell
$ composer require thisliu/mixin -vvv
```

## 配置
```php
$config = [
    xxx
];
```

### 返回值

所有的接口调用都会返回 [`Thisliu\Mixin\Http\Response`](https://github.com/thisliu/mixin-sdk-php/blob/main/src/Http/Response.php) 对象，改对象提供了以下便捷方法：

```php
array|null $response->toArray(); // 获取响应内容数组转换结果                                                
object $response->toObject(); // 获取对象格式的返回值
bool $response->isXML(); // 检测返回内容是否为 XML
string $response->getContents(); // 获取原始返回内容
```

## 使用
```php
use Thisliu\Mixin\Mixin;

$app = new Mixin($config);

$response = $app->get('/me');
```

## License

MIT