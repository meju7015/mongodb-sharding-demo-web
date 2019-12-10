# UFramework
- 기본 캐릭터셋은 EUC-KR 입니다. 
- MVC 패턴과 메소드 체이닝 패턴을 이용했습니다.
- jQuery 미사용 request 라이브러리를 포함합니다.
- UDebug 가 추가되었습니다.
- Command 가 추가되었습니다.

## About
<strong>PHP 5.1.4</strong> 이상에서 사용할수 있는 Nano Framework<br>

## Infomation
- Bootstrap.php 의 autoLoad() function 의 변수중, $path 에 해당하는 배열 순서를 변경하지 마세요.

## Install
- Apache vhost.conf
```apache
<VirtualHost *:80>
    ServerName uframework.com
    DocumentRoot ~/uframework/public
</VirtualHost>
```

- Apache httpd.conf
```apache
<Directory "~/uframework/public">
    AllowOverride All
</Directory>
```

- Bootstrap.php<br>
데이터베이스 커넥터 정보를 입력합니다.
(LOCAL_CHANEL, DEVELOPE_CHANEL, PRODUCT_CHANEL) Default 는 프로덕트 채널입니다.
```php
public function __constracut()
{
    $this->rootDir = Config::getRootDir();
    DBConfig::setDatabaseInfo(LOCAL_CHANEL);
}
```

- .env.sample 복사  
env 파일을 복사하여 해당 서버환경에 맞게 데이터 입력.

## Use sub directory
- /public/.htaccess -> /uframework/ 최상단으로 이동시킵니다.

```apacheconfig
RewriteEngine On
DirectoryIndex public/index.php
RewriteBase /uframework/public
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule . index.php [L]
``` 
해당 내용을 넣어주세요.

- config.php 의 IS_SUBDIRECTORY = true 로 변경합니다.
- config.php 의 DEFAULT_SITE 값에 상단 디렉토리명을 명시해주세요.

## UDebug
디버그 클래스 입니다. 기본적으로 session, get/post, router, controller, model 등의
정보를 가지며 추가로 디버깅이 필요한 경우에 store 메소드를 이용하여 추가할수 있습니다. 

```php
UDebug::store();
```

Exception 이 발생한 경우 트레이서에서 자동으로 모든 디버깅 변수를 출력해줍니다. 또한 
디버깅 데이터가 필요한 경우 아래 메소드를 호출하여 전달 받습니다.
```php
UDebug::pop();
``` 

현재 상황에서 강제로 모든 디버깅 정보 또는 트레이스 정보를 얻고자 할때는
아래 메소드를 이용하여 현재 입력된 모든 디버깅 정보와 트레이스 정보를 얻을수 있습니다.
```php
UDebug::display();
```

## Command
서버에서 직접 호출 가능한 command 를 추가했습니다.<br>
크론 작업 및 컨트롤러/모델/라우터 생성 및 제거기능을 포함합니다.<br><br>

- 컨트롤러 생성
/app/controllers/ 디렉토리에 {controllerName}Controller.php 의 이름으로 생성됩니다. 
```sh
php command make:controller {controllerName}
```

- 라우터 생성
/routes/ 디렉토리에 {routerName}Route.php 의 이름으로 생성됩니다.<br>
{controllerName}을 입력하면 해당 라우터를 컨트롤러 이름으로 바인딩 시켜주지만 입력하지 않아도 기본 라우터 이름을 따라갑니다.
```sh
php command make:router {routerName} {controllerName = routerName}
```

- 모델 생성
/app/models/ 디렉토리에 {modelName}Model.php 의 이름으로 생성됩니다.
```sh
php command make:model {modelName}
```

- 페이지를 한번에 생성
컨트롤러/모델/라우터가 각각 생성됩니다.
```sh
php command make:all {controllerName}
```

## Security
- XSS cleaner  
Security class 에서 XSS cleaner 를 이용할수 있습니다.  
librarry 에 포함되어있는 static Security class의 cleanXSS($data) 를 이용하세요.
```php
$HTMLContents = Security::cleanXSS($_POST['html_contents']);
```

- CSRF token  
모든 POST 요청에 대하여 csrf 토큰을 생성하여 form 유효성에 대해서 검사합니다.  
만약, 이 기능을원하지 않는다면 /app/core/Router 클래스로 이동하여 post function 을 수정하세요.  
일반적으로 form 전송시, 또는 ajax 이용시에 CSRF_TOKEN 을 입력해 주면 됩니다.
```html
<form action="/bbs/write/" method="post">
    <input type="hidden" name="CSRF_TOKEN" value="<?=$CSRF_TOKEN?>">
</form>
```
스크립트에서 이용 가능한 config 클래스는 /public/js/uframework/config 에 있습니다. 
이 js 파일은 header layout 규칙을 따른다면, View 클래스에 의해 자동으로 로딩됩니다.
```javascript
$.ajax({
    data: { CSRF_TOKEN:config.CSRF_TOKEN }
})
```

코드에서 CSRF_TOKEN을 얻으려면 해당클래스를 이용하세요.
```php
Security::getCSRFDetect();
```
## QueryBuilder
- queryBuilder 경로는 /uframework/libraries/queryBuilder 입니다.
- 초기화 및 사용
```php
$pdo = [
    "dsn" => "host=localhost;port=3306;db=test",
    "user" => "root",
    "pass" => "toor"
];

$builder = new Query(Connection::coneect($pdo));

$builder
    ->table('test.Member')
    ->where('MemberEmail', 'meju7015@gmail.com')
    ->where('MemberPassword', $this->password('123123123'));

$data = $builder->get();
```

