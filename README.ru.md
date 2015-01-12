# yiidreamteam\smsru\Api #

PHP-класс для работы с api сервиса [sms.ru](http://yiidreamteam.sms.ru).
Улучшенная и переработанная версия класса [sms_ru](https://github.com/zelenin/sms_ru) Александра Зеленина.

## Установка ##

Предпочтительным способом установки является установка через [composer](http://getcomposer.org/download/).

Запустите

    php composer.phar require --prefer-dist yii-dream-team/smsru "*"

или добавьте

    "yii-dream-team/smsru": "*"

в секцию `require` вашего composer.json

## Использование ##

Авторизация:

    $api = new \yiidreamteam\smsru\Api($apiId);

Отправка SMS:

    $api->send('79112223344', 'Текст SMS');
    $api->send('79112223344,79115556677,79118889900', 'Текст SMS');
    $api->send('79112223344', 'Текст SMS', 'Имя отправителя', time(), $transliteration = false, $test = true);
    
Множественная отправка SMS:

    $messages = [
        ['79112223344', 'Текст СМС'],
        ['79115556677', 'Текст СМС']
    ];
    $api->sendMultiple($messages, 'Имя отправителя', time(), $transliteration = false, $test = true);

Статус SMS:

    $api->status('SMS id');

Стоимость SMS:

    $api->cost('79112223344', 'Текст SMS');

Баланс:

    $api->balance();

Дневной лимит:

    $api->limit();

Отправители:

    $api->senders();


Добавить номер в стоплист:

    $api->stopListAdd('79112223344', 'Примечание');

Удалить номер из стоп-листа:

    $api->stopListDel('79112223344');

Получить номера стоплиста:

    $api->stopListGet();

## Ссылки

* [Сервис sms.ru](http://http://yiidreamteam.sms.ru)
* [Официальный сайт](http://yiidreamteam.com/php/smsru)
* [Исходный код на GitHub](https://github.com/yii-dream-team/smsru)
* [Composer пакет на Packagist](https://packagist.org/packages/yii-dream-team/smsru)
* Первоисточник: [Александр Зеленин](https://github.com/zelenin/), e-mail: [aleksandr@zelenin.me](mailto:aleksandr@zelenin.me)
