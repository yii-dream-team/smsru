# yiidreamteam\smsru\Api

PHP class for working with [sms.ru](http://yiidreamteam.sms.ru) api by [Yii Dream Team](http://yiidreamteam.com/).
Improved and refactored version of the [sms_ru](https://github.cm/zelenin/sms_ru) class by Aleksandr Zelenin.

## Installation ##

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

    php composer.phar require --prefer-dist yii-dream-team/smsru "*"

or add

    "yii-dream-team/smsru": "*"

to the `require` section of your composer.json.

## Usage

Authorization:

    $api = new \yiidreamteam\smsru\Api($apiId);

Sending text message:

    $api->send('79112223344', 'Text message');
    $api->send('79112223344,79115556677,79118889900', 'Text message');
    $api->send('79112223344', 'Text message', 'Sender', time(), $transliteration = false, $test = true);
    
Sending multiple texts:

    $messages = [
        ['79112223344', 'Text message'],
        ['79115556677', 'Text message #2']
    ];
    $api->sendMultiple($messages, 'Sender', time(), $transliteration = false, $test = true);

Message status:

    $api->status('SMS id');

Message cost:

    $api->cost('79112223344', 'Text message');

Balance:

    $api->balance();

Daily limit:

    $api->limit();

Senders:

    $api->senders();


Adding number to the stop list:

    $api->stopListAdd('79112223344', 'Some note');

Removing number from the stop list

    $api->stopListDel('79112223344');

Obtaining the stop list:

    $api->stopListGet();

## Links

* [sms.ru service](http://yiidreamteam.sms.ru)
* [Official site](http://yiidreamteam.com/php/smsru)
* [Source code on GitHub](https://github.com/yii-dream-team/smsru)
* [Composer package on Packagist](https://packagist.org/packages/yii-dream-team/smsru)
* Origin: [Aleksandr Zelenin](https://github.com/zelenin/), e-mail: [aleksandr@zelenin.me](mailto:aleksandr@zelenin.me)