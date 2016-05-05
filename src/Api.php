<?php
namespace yiidreamteam\smsru;

use GuzzleHttp\Client;

/**
 * Class Api
 *
 * @package yiidreateam\smsru
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */
class Api
{
    const API_URL = 'http://sms.ru/';
    const METHOD_SMS_SEND = 'sms/send';
    const METHOD_SMS_STATUS = 'sms/status';
    const METHOD_SMS_COST = 'sms/cost';
    const METHOD_MY_BALANCE = 'my/balance';
    const METHOD_MY_LIMIT = 'my/limit';
    const METHOD_MY_SENDERS = 'my/senders';
    const METHOD_AUTH_GET_TOKEN = 'auth/get_token';
    const METHOD_STOP_LIST_ADD = 'stoplist/add';
    const METHOD_STOP_LIST_DEL = 'stoplist/del';
    const METHOD_STOP_LIST_GET = 'stoplist/get';
    protected $apiId;
    /** @var Client */
    protected $client = null;
    protected $authParams = [];

    public function __construct($apiId)
    {
        $this->apiId = $apiId;
    }

    /**
     * Sends message
     *
     * @param string $to
     * @param string $text
     * @param string|null $from
     * @param integer|null $time
     * @param bool $transliteration
     * @param bool $test
     * @param string|null $partnerId
     * @return array
     *
     * @link http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=sms/send
     */
    public function send(
        $to,
        $text,
        $from = null,
        $time = null,
        $transliteration = false,
        $test = false,
        $partnerId = null
    ) {
        $messages = [[$to, $text]];
        return $this->sendMultiple($messages, $from, $time, $transliteration, $test, $partnerId);
    }

    /**
     * Sends multiple messages
     *
     * @param array $messages
     * @param string|null $from
     * @param integer|null $time
     * @param bool $transliteration
     * @param bool $test
     * @param string|null $partnerId
     * @return array
     * @throws \Exception
     *
     * @link http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=sms/send
     */
    public function sendMultiple(
        $messages,
        $from = null,
        $time = null,
        $transliteration = false,
        $test = false,
        $partnerId = null
    ) {
        $params = [
            'from' => $from,
            'time' => $time,
            'translit' => $transliteration,
            'test' => $test,
            'partner_id' => $partnerId,
        ];
        array_filter($params);

        foreach ($messages as $message) {
            $params['multi'][$message[0]] = $message[1];
        }

        $result = $this->callInternal(static::METHOD_SMS_SEND, $params);

        if ($result['code'] == 100) {
            foreach ($result['data'] as $item) {
                if (strpos($item, '=') === false) {
                    $result['ids'][] = $item;
                } else {
                    $response = explode('=', $item);
                    $result[$response[0]] = $response[1];
                }
            }
        }

        return $result;
    }

    /**
     * Makes api call and returns call result as array
     *
     * @param string $method
     * @param array $params
     * @return array
     * @throws \Exception
     */
    protected function callInternal($method, $params = [])
    {
        $response = $this->call($method, $params);
        $response = explode("\n", rtrim($response));
        $code = array_shift($response);
        return [
            'code' => $code,
            'description' => $this->getResponseText(static::METHOD_SMS_SEND, $code),
            'data' => $response,
        ];
    }

    /**
     * Makes api call
     *
     * @param $method
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function call($method, $params = [])
    {
        if (empty($this->client)) {
            $this->client = new Client([
                'base_uri' => static::API_URL,
            ]);
        }

        if (empty($this->authParams)) {
            $this->authParams = [
                'api_id' => $this->apiId
            ];
        }

        $params = array_merge($params, $this->authParams);

        try {
            $response = $this->client->post($method, ['form_params' => $params]);
            if ($response->getStatusCode() != 200) {
                throw new \Exception('Api http error: ' . $response->getStatusCode(), $response->getStatusCode());
            }
            return (string)$response->getBody();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Converts result code into the text
     *
     * @param $method
     * @param $code
     * @return null
     */
    protected function getResponseText($method, $code)
    {
        $responseCode = [
            static::METHOD_SMS_SEND => [
                '100' => 'Сообщение принято к отправке. На следующих строчках вы найдете идентификаторы отправленных сообщений в том же порядке, в котором вы указали номера, на которых совершалась отправка.',
                '200' => 'Неправильный api_id.',
                '201' => 'Не хватает средств на лицевом счету.',
                '202' => 'Неправильно указан получатель.',
                '203' => 'Нет текста сообщения.',
                '204' => 'Имя отправителя не согласовано с администрацией.',
                '205' => 'Сообщение слишком длинное (превышает 8 СМС).',
                '206' => 'Будет превышен или уже превышен дневной лимит на отправку сообщений.',
                '207' => 'На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей.',
                '208' => 'Параметр time указан неправильно.',
                '209' => 'Вы добавили этот номер (или один из номеров) в стоп-лист.',
                '210' => 'Используется GET, где необходимо использовать POST.',
                '211' => 'Метод не найден.',
                '212' => 'Текст сообщения необходимо передать в кодировке UTF-8 (вы передали в другой кодировке).',
                '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
                '230' => 'Сообщение не принято к отправке, так как на один номер в день нельзя отправлять более 60 сообщений.',
                '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился).',
                '301' => 'Неправильный пароль, либо пользователь не найден.',
                '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс).'
            ],
            static::METHOD_SMS_STATUS => [
                '-1' => 'Сообщение не найдено.',
                '100' => 'Сообщение находится в нашей очереди.',
                '101' => 'Сообщение передается оператору.',
                '102' => 'Сообщение отправлено (в пути).',
                '103' => 'Сообщение доставлено.',
                '104' => 'Не может быть доставлено: время жизни истекло.',
                '105' => 'Не может быть доставлено: удалено оператором.',
                '106' => 'Не может быть доставлено: сбой в телефоне.',
                '107' => 'Не может быть доставлено: неизвестная причина.',
                '108' => 'Не может быть доставлено: отклонено.',
                '200' => 'Неправильный api_id.',
                '210' => 'Используется GET, где необходимо использовать POST.',
                '211' => 'Метод не найден.',
                '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
                '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился).',
                '301' => 'Неправильный пароль, либо пользователь не найден.',
                '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс).'
            ],
            static::METHOD_SMS_COST => [
                '100' => 'Запрос выполнен. На второй строчке будет указана стоимость сообщения. На третьей строчке будет указана его длина.',
                '200' => 'Неправильный api_id.',
                '202' => 'Неправильно указан получатель.',
                '207' => 'На этот номер нельзя отправлять сообщения.',
                '210' => 'Используется GET, где необходимо использовать POST.',
                '211' => 'Метод не найден.',
                '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
                '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился).',
                '301' => 'Неправильный пароль, либо пользователь не найден.',
                '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс).'
            ],
            static::METHOD_MY_BALANCE => [
                '100' => 'Запрос выполнен. На второй строчке вы найдете ваше текущее состояние баланса.',
                '200' => 'Неправильный api_id.',
                '210' => 'Используется GET, где необходимо использовать POST.',
                '211' => 'Метод не найден.',
                '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
                '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился).',
                '301' => 'Неправильный пароль, либо пользователь не найден.',
                '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс).'
            ],
            static::METHOD_MY_LIMIT => [
                '100' => 'Запрос выполнен. На второй строчке вы найдете ваше текущее дневное ограничение. На третьей строчке количество сообщений, отправленных вами в текущий день.',
                '200' => 'Неправильный api_id.',
                '210' => 'Используется GET, где необходимо использовать POST.',
                '211' => 'Метод не найден.',
                '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
                '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился).',
                '301' => 'Неправильный пароль, либо пользователь не найден.',
                '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс).'
            ],
            static::METHOD_MY_SENDERS => [
                '100' => 'Запрос выполнен. На второй и последующих строчках вы найдете ваших одобренных отправителей, которые можно использовать в параметре &from= метода sms/send.',
                '200' => 'Неправильный api_id.',
                '210' => 'Используется GET, где необходимо использовать POST.',
                '211' => 'Метод не найден.',
                '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
                '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился).',
                '301' => 'Неправильный пароль, либо пользователь не найден.',
                '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс).'
            ],
            static::METHOD_STOP_LIST_ADD => [
                '100' => 'Номер добавлен в стоплист.',
                '202' => 'Номер телефона в неправильном формате.'
            ],
            static::METHOD_STOP_LIST_DEL => [
                '100' => 'Номер удален из стоплиста.',
                '202' => 'Номер телефона в неправильном формате.'
            ],
            static::METHOD_STOP_LIST_GET => [
                '100' => 'Запрос обработан. На последующих строчках будут идти номера телефонов, указанных в стоплисте в формате номер;примечание.'
            ]
        ];
        return isset($responseCode[$method][$code])
            ? $responseCode[$method][$code]
            : null;
    }

    /**
     * Returns message delivery status
     *
     * @param string $id
     * @return array
     * @throws \Exception
     *
     * @links http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=sms/status
     */
    public function status($id)
    {
        return $this->callInternal(static::METHOD_SMS_STATUS, compact('id'));
    }

    /**
     * Returns message cost
     *
     * @param string $to
     * @param string $text
     * @return array
     * @throws \Exception
     *
     * @link http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=sms/cost
     */
    public function cost($to, $text)
    {
        $result = $this->callInternal(static::METHOD_SMS_COST, compact('to', 'text'));
        if ($result['code'] != 100) {
            return $result;
        }
        $result['cost'] = $result['data'][0];
        $result['number'] = $result['data'][1];
        return $result;
    }

    /**
     * Returns user's balance
     *
     * @return array
     * @throws \Exception
     *
     * @link http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=my/balance
     */
    public function balance()
    {
        $result = $this->callInternal(static::METHOD_MY_BALANCE);
        if ($result['code'] != 100) {
            return $result;
        }
        $result['balance'] = $result['data'][0];
        return $result;
    }

    /**
     * Returns information about the daily limit
     *
     * @return array
     *
     * @link http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=my/limit
     */
    public function limit()
    {
        $result = $this->callInternal(static::METHOD_MY_LIMIT);
        if ($result['code'] != 100) {
            return $result;
        }
        $result['total'] = $result['data'][0];
        $result['current'] = $result['data'][1];
        return $result;
    }

    /**
     * Returns senders list
     *
     * @return array
     *
     * @see http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=my/senders
     */
    public function senders()
    {
        $result = $this->callInternal(static::METHOD_MY_SENDERS);
        if ($result['code'] != 100) {
            return $result;
        }
        $result['senders'] = array_values($result['data'][0]);
        return $result;
    }

    /**
     * Adds phone to the stop list
     *
     * @param $phone
     * @param $text
     * @return array
     *
     * @see http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=stoplist/add
     */
    public function stopListAdd($phone, $text)
    {
        return $this->callInternal(static::METHOD_STOP_LIST_ADD, [
            'stoplist_phone' => $phone,
            'stoplist_text' => $text
        ]);
    }

    /**
     * Removes phone from the stop list
     *
     * @param string $phone
     * @return array
     *
     * @see http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=stoplist/del
     */
    public function stopListDel($phone)
    {
        return $this->callInternal(static::METHOD_STOP_LIST_DEL, ['stoplist_phone' => $phone]);
    }

    /**
     * Returns stop list
     *
     * @return array
     *
     * @see http://yiidreamteam.sms.ru/?panel=api&subpanel=method&show=stoplist/get
     */
    public function stopListGet()
    {
        $result = $this->callInternal(static::METHOD_STOP_LIST_GET);
        $list = [];
        foreach ($result['data'] as $item) {
            $t = explode(';', $item);
            $list[] = [
                'phone' => $t[0],
                'text' => $t[1],
            ];
        }
        $result['list'] = $list;
        return $result;
    }
}
