# WishBox VK

Переиспользуемая библиотека интеграции с VK API для расширений Joomla.

Пакет устанавливает библиотеку `lib_wishboxvk` и регистрирует пространство имён
`WishboxVkLibrary`. Библиотека не зависит от RadicalMart или других компонентов и
может использоваться в плагинах, компонентах, модулях и консольных командах Joomla.

## Возможности

- вызов любых методов VK API;
- выполнение VKScript через метод `execute`;
- обновление цены и состояния товаров VK Market пакетами до 25 позиций;
- получение заказов сообщества и их товарных позиций;
- проверка `group_id` и секрета VK Callback API;
- обработка запроса подтверждения Callback API;
- отдельные исключения для ошибок API, captcha и callback-запросов;
- внедрение HTTP-клиента для тестирования.

## Требования

- PHP 8.1 или новее;
- Joomla с доступными пакетами `Joomla\Http` и `Joomla\CMS\Log`;
- токен доступа VK для методов, обращающихся к VK API.

## Установка

Соберите `pkg_wishboxvk.zip` по инструкции ниже и установите его через менеджер
расширений Joomla. После установки классы пространства имён `WishboxVkLibrary`
загружаются автозагрузчиком Joomla.

Расширения, использующие библиотеку, должны проверять её наличие до регистрации
своих сервисов:

```php
use RuntimeException;
use WishboxVkLibrary\Api\VkApiClient;

if (!class_exists(VkApiClient::class))
{
	throw new RuntimeException('The WishBox VK library must be installed.');
}
```

## Использование VK API

`VkApiClient` автоматически добавляет `access_token` и версию API к каждому
запросу. По умолчанию используется версия `5.199`.

```php
use WishboxVkLibrary\Api\VkApiClient;

$vkApiClient = new VkApiClient('VK_ACCESS_TOKEN');

$response = $vkApiClient->call(
	'users.get',
	[
		'user_ids' => '1',
		'fields'   => 'city,country',
	]
);
```

Для отдельного запроса можно указать другую версию API:

```php
$response = $vkApiClient->call('market.get', ['owner_id' => -123], '5.131');
```

Выполнение VKScript:

```php
$response = $vkApiClient->execute(
	'return API.users.get({"user_ids": "1"});'
);
```

Для работы через HTTPS-прокси передайте его URL и общий секрет. Клиент отправит
секрет в заголовке `X-VK-Proxy-Token`:

```php
$vkApiClient = new VkApiClient(
	accessToken: 'VK_ACCESS_TOKEN',
	apiUrl: 'https://proxy.example/vk-api-proxy.php/',
	proxyToken: 'PROXY_SHARED_SECRET'
);
```

Если прокси авторизует сервер по IP, параметр `proxyToken` можно не указывать.

## VK Market

`VkMarketService` принимает идентификатор сообщества без минуса. Для каждого
товара создаётся DTO `VkMarketItemUpdate`.

```php
use WishboxVkLibrary\Api\VkApiClient;
use WishboxVkLibrary\Dto\VkMarketItemUpdate;
use WishboxVkLibrary\Service\VkMarketService;

$vkApiClient = new VkApiClient('VK_ACCESS_TOKEN');
$vkMarketService = new VkMarketService($vkApiClient, 123456);

$result = $vkMarketService->editProducts(
	[
		new VkMarketItemUpdate(
			itemId: 1001,
			price: 1490.00,
			oldPrice: 1690.00,
			deleted: false
		),
	]
);
```

Один вызов поддерживает от 1 до 25 товаров. Если VK запросил captcha, повторите
вызов с `captcha_sid` и введённым пользователем ключом:

```php
$result = $vkMarketService->editProducts($items, $captchaSid, $captchaKey);
```

## Заказы VK Market

```php
use WishboxVkLibrary\Api\VkApiClient;
use WishboxVkLibrary\Service\VkOrderService;

$vkApiClient = new VkApiClient('VK_ACCESS_TOKEN');
$vkOrderService = new VkOrderService($vkApiClient, 123456);

$orders = $vkOrderService->getGroupOrders(limit: 20, offset: 0);
$items = $vkOrderService->getOrderItems(userId: 42, orderId: 100500);
```

`getGroupOrders()` возвращает объект ответа VK. `getOrderItems()` возвращает список
объектов товарных позиций.

## VK Callback API

```php
use WishboxVkLibrary\Service\VkCallbackService;

$vkCallbackService = new VkCallbackService(
	groupId: 123456,
	secret: 'CALLBACK_SECRET',
	confirmationCode: 'CONFIRMATION_CODE'
);

$payload = json_decode($requestBody, false, 512, JSON_THROW_ON_ERROR);

if (!is_object($payload))
{
	throw new RuntimeException('VK callback payload must be an object.');
}

$vkCallbackService->validate($payload);
$confirmationResponse = $vkCallbackService->getConfirmationResponse($payload);

if ($confirmationResponse !== null)
{
	echo $confirmationResponse;
}
```

Для Joomla-плагинов доступны `VkCallbackServiceAwareInterface` и
`VkCallbackServiceAwareTrait`.

## Обработка ошибок

```php
use WishboxVkLibrary\Exception\VkApiException;
use WishboxVkLibrary\Exception\VkCaptchaRequiredException;

try
{
	$response = $vkApiClient->call('users.get', ['user_ids' => '1']);
}
catch (VkCaptchaRequiredException $exception)
{
	$captchaImage = $exception->getCaptchaImage();
	$captchaSid = $exception->getCaptchaSid();
}
catch (VkApiException $exception)
{
	// Ошибка HTTP, JSON или VK API.
}
```

Ошибки отдельных операций внутри VKScript записываются в журнал Joomla в
категорию `wishboxvk`.

## Структура проекта

```text
pkg_wishboxvk/
├── language/
├── lib_wishboxvk/
│   ├── language/
│   ├── src/
│   │   ├── Api/
│   │   ├── Dto/
│   │   ├── Exception/
│   │   └── Service/
│   └── lib_wishboxvk.xml
└── pkg_wishboxvk.xml
```

## Сборка пакета

В корне репозитория выполните:

```bash
cd lib_wishboxvk
zip -r ../lib_wishboxvk.zip . -x '.DS_Store'
cd ..
zip -r pkg_wishboxvk.zip pkg_wishboxvk.xml language lib_wishboxvk.zip -x '.DS_Store'
```

Полученный `pkg_wishboxvk.zip` можно установить через менеджер расширений Joomla.

## Лицензия

GNU General Public License version 2 или более поздняя версия.
