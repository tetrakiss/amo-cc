<?php

namespace App\Http\Controllers;
use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NullTagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\BirthdayCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Models\Unsorted\FormsMetadata;
use League\OAuth2\Client\Token\AccessToken;

class amoLeads extends Controller
{
//$clientId = $_ENV['CLIENT_ID'];
//$clientSecret = $_ENV['CLIENT_SECRET'];
//$redirectUri = $_ENV['CLIENT_REDIRECT_URI'];

///$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
function refreshToken() {

    $token = DB::connection('mysql')->table('tokens')->first();

    $subdomain = 'kirillovcorsocomocom'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */


$data = [
    'client_id' => '51663aab-2fae-42df-81fb-f24496dc0380',
    'client_secret' => '1GE4bWFWv6AUhuHw2zUIob5V08WlryXfBRzf0yS9UMX5Bf235UdGhle3vpbz30He',
    'grant_type' => 'refresh_token',
    'refresh_token' => $token->refresh_token,
    'redirect_uri' => 'https://amo.corsocomo.com/callback',
];

/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
    400 => 'Bad request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not found',
    500 => 'Internal server error',
    502 => 'Bad gateway',
    503 => 'Service unavailable',
];

try
{
    /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
    if ($code < 200 || $code > 204) {
        dd($code);
       // throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
    }
}
catch(Exception $e)
{
    die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);

$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает

DB::connection('mysql')->table('tokens')
->where('id', 1)
->update(['access_token' => $access_token,
'refresh_token' =>$refresh_token,
'expires_in'=>$expires_in,
'created_at'=>now()
]);
print_r($response);

}
function getauthcode() {

$subdomain = 'kirillovcorsocomocom'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса
//def50200824a865924bbfedfd127793d6be35e05a66d2e8c8838e0134789412ff5be1256f76b0db710736b11a257008d833d417f8560e6d89046ab4d74382ec0128b2d5f6912992a15c8a5326985be0b4cd67866ba434ac81b671e6c23bc63ed488be9c01a891530bc749ccd7c8fea1325aae8e0f336caf27fd5ce8908610ff64a90a8e11cfdf13af3ede2b54a9f3d17d49194259519dfd10ae5eb3a046a8a0106ea56966e482b6c6443e9105d76e178c9e01692bf201e45e1ea8032741b7b83c31cf1bdeff56a541b3e40fc60a17ef9e03d43c4ad40592a75a98481dc710197aa359746f3c0c037c22158f11b987730696727bba277b1e129d8b32cac44722df8e9adef4a16a5dd42efe1aad587e078094b20320055a191e6d49fd4afe66c14e1dbdb7d54f82e197fd4e023a5ddd568e7e3dde01af3e695c05a0ac7409c1208323059298c4162ef2cebd98b5cc94d2931b8d57e5452e7abd836ffadbce1907d64fea15976e09b2fa53dc1640243f88a7fabaa275f0ea61f06d25e882146cf0b953299de69dbec01ce5b8db93efc8cf4710a56fcf797ff0be088ab0cca4e77112f5af50f9717d01a19edf4c2e3b3291938346a85c5f831c9050a298b1a548afc438d4db7103a10a05ccc4613d6d851582ce569467324f70c372204f1f4b1fe523b068e10
/** Соберем данные для запроса */
$data = [
    'client_id' => '51663aab-2fae-42df-81fb-f24496dc0380',
    'client_secret' => '1GE4bWFWv6AUhuHw2zUIob5V08WlryXfBRzf0yS9UMX5Bf235UdGhle3vpbz30He',
    'grant_type' => 'authorization_code',
    'code' => 'def50200824a865924bbfedfd127793d6be35e05a66d2e8c8838e0134789412ff5be1256f76b0db710736b11a257008d833d417f8560e6d89046ab4d74382ec0128b2d5f6912992a15c8a5326985be0b4cd67866ba434ac81b671e6c23bc63ed488be9c01a891530bc749ccd7c8fea1325aae8e0f336caf27fd5ce8908610ff64a90a8e11cfdf13af3ede2b54a9f3d17d49194259519dfd10ae5eb3a046a8a0106ea56966e482b6c6443e9105d76e178c9e01692bf201e45e1ea8032741b7b83c31cf1bdeff56a541b3e40fc60a17ef9e03d43c4ad40592a75a98481dc710197aa359746f3c0c037c22158f11b987730696727bba277b1e129d8b32cac44722df8e9adef4a16a5dd42efe1aad587e078094b20320055a191e6d49fd4afe66c14e1dbdb7d54f82e197fd4e023a5ddd568e7e3dde01af3e695c05a0ac7409c1208323059298c4162ef2cebd98b5cc94d2931b8d57e5452e7abd836ffadbce1907d64fea15976e09b2fa53dc1640243f88a7fabaa275f0ea61f06d25e882146cf0b953299de69dbec01ce5b8db93efc8cf4710a56fcf797ff0be088ab0cca4e77112f5af50f9717d01a19edf4c2e3b3291938346a85c5f831c9050a298b1a548afc438d4db7103a10a05ccc4613d6d851582ce569467324f70c372204f1f4b1fe523b068e10',
    'redirect_uri' => 'https://amo.corsocomo.com/callback',
];

/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
    400 => 'Bad request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not found',
    500 => 'Internal server error',
    502 => 'Bad gateway',
    503 => 'Service unavailable',
];

try
{
    /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
    if ($code < 200 || $code > 204) {
        throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
    }
}
catch(Exception $e)
{
    die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);

$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает
DB::connection('mysql')->table('tokens')
->where('id', 1)
->update(['access_token' => $access_token,
'refresh_token' =>$refresh_token,
'expires_in'=>$expires_in,
'created_at'=>now()
]);

print_r($response);
}

function testupdate(){
    DB::connection('mysql')->table('tokens')
->where('id', 1)
->update(['access_token' => 'test',
'refresh_token' =>'test',
'expires_in'=>86400,
'created_at'=>now()
]);
}
public function callback (Request $request){
 $code=$request->code;
 $subdomain = 'kirillovcorsocomocom'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса
/** Соберем данные для запроса */

$data = [
    'client_id' => $_ENV['CLIENT_ID'],
    'client_secret' =>$_ENV['CLIENT_SECRET'] ,
    'grant_type' => 'authorization_code',
    'code' =>$code,
    'redirect_uri' => $_ENV['CLIENT_REDIRECT_URI'],
];

/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
    400 => 'Bad request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not found',
    500 => 'Internal server error',
    502 => 'Bad gateway',
    503 => 'Service unavailable',
];

try
{
    /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
    if ($code < 200 || $code > 204) {
        throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
    }
}
catch(Exception $e)
{
    die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);

$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает
DB::connection('mysql')->table('tokens')
->where('id', 1)
->update(['access_token' => $access_token,
'refresh_token' =>$refresh_token,
'expires_in'=>$expires_in,
'created_at'=>now()
]);

print_r($response);

}

public function getorder(){
    $order = DB::connection('mysql2')->table('openorder')
    ->join('openorder_status', 'openorder.order_status_id', '=', 'openorder_status.order_status_id')
    ->select('openorder.*', 'openorder_status.name AS statusName')->orderBy('openorder.order_id', 'desc')->first();
    $total = (int) DB::connection('mysql2')->table('openorder_total')
                    ->where('order_id', $order->order_id)
                      ->where('code', 'total')
                    ->value('value');

    $delivery = (int) DB::connection('mysql2')->table('openorder_total')
                  ->where('order_id', $order->order_id)
                  ->where('code', 'shipping')
                  ->value('value');
                  if($delivery==NULL){
                    $delivery=0;
                  }

    $o = (object) array(
        'id' => $order->order_id,
        'payment_method'=>$order->payment_method,
        'date' => $order->date_added,
        'total'=>$total ,
        'firstname' => $order->shipping_firstname,
        'lastname' => $order->shipping_lastname,
        'delivery' => $delivery,
        'city' => $order->payment_city,
        'address1' => $order->payment_address_1,
        'address2' => $order->payment_address_2,
        'zone' => $order->shipping_zone,
        'telephone' => str_replace(array('(', ')', '+', '-', '.', '/', ' '), '', $order->telephone),
        'email' => $order->email,
        'metro' => $order->shipping_metro,
        'comment' => $order->comment,
        'postcode' => $order->shipping_postcode,
        'deliveryDate' => str_replace('0000-00-00', '0001-01-01', $order->delivery_date),
        'deliveryTime' => $order->delivery_time,
        'paymentСode' => $order->payment_code,
        'discountCard' => $order->shipping_company,
        'deliveryPrice' => (int) $delivery,
       );
    $p = DB::connection('mysql2')->table('openorder_product')
    //->leftJoin('openorder_option', 'openorder_product.order_id', '=', 'openorder_option.order_id')
    ->join('openproduct', 'openorder_product.product_id', '=', 'openproduct.product_id')
    ->select('openorder_product.product_id', 'openorder_product.model', 'openorder_product.order_product_id', 'openorder_product.price', 'openorder_product.quantity', 'openproduct.upc AS uid')
    ->where('openorder_product.order_id', $order->order_id)
    ->get();
foreach ($p as $product) {
  $option = DB::connection('mysql2')->table('openorder_option')
->where('order_id', $order->order_id)
->where('order_product_id', $product->order_product_id)
->value('value');
 
  
  $o->products[] = (object) array(
    'uid' => (string) $product->model,
    'size' => str_replace('.', ',', $option),
    'quantity' => (int) $product->quantity,
    'price' => (int) $product->price,
  );

}
return $o;

}

function addLead(){
    $fieldsProduct = [
        985831,
        987139,
        987141,
        987145 
    ];
    $fieldsURL = [
        986541,
        986555,
        986557,
        986559
    ];
    //Товар1 985831
    //url1 986541
    //Товар2 986563
    //url2 986555
    //Товар3 986565
    //url3 986557
    //Товар4 986567
    //url4 986559
    //доставка 986561
    // оплата 986543
    $order_data=$this->getorder();
    $clientId = $_ENV['CLIENT_ID'];
    $clientSecret = $_ENV['CLIENT_SECRET'];
    $redirectUri = $_ENV['CLIENT_REDIRECT_URI'];

    $accessToken =  DB::connection('mysql')->table('tokens')->first();
//dd( $accessToken);
    $token = new AccessToken([
        'access_token' => $accessToken->access_token,
        'refresh_token' => $accessToken->refresh_token,
        'expires' => $accessToken->expires_in,
        'baseDomain' => $accessToken->baseDomain,
    ]);

    $apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

   
    $accessToken = $token;

    $apiClient->setAccessToken($accessToken)->setAccountBaseDomain($accessToken->getValues()['baseDomain']);



    //Представим, что у нас есть данные, полученные из сторонней системы
$externalData = [
    [
        'is_new' => true,
        'price' => 10700,
        'name' => 'Заказ с сайта 124870',
        'contact' => [
            'first_name' => 'Дарья',
            'last_name' => 'Рыбалко',
            'phone' => '7(985)152-6248',
            'email' => 'test@test.ru',
        ],
        'delivery'=>'Москва, оболенский пер 10 с1 кв 555',
        
        //'tag' => 'Новый клиент',
        'external_id' => '124870',
    ]
];


$leadsCollection = new LeadsCollection();

//Создадим модели и заполним ими коллекцию
//foreach ($externalData as $externalLead) {
    $leadCustomFieldsValues = new CustomFieldsValuesCollection();
    $deliveryAdress = new TextCustomFieldValuesModel();
    $deliveryAdress->setFieldId(985827);
    $deliveryAdress->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($order_data->city.' '.$order_data->address1.' '.$order_data->address2.' '.$order_data->metro))
    );
    $leadCustomFieldsValues->add($deliveryAdress);


    $deliveryPrice = new TextCustomFieldValuesModel();
    $deliveryPrice->setFieldId(986561);
    $deliveryPrice->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($order_data->delivery.' ₽'))
    );
    $leadCustomFieldsValues->add($deliveryPrice);

    $payment_method = new TextCustomFieldValuesModel();
    $payment_method->setFieldId(986543);
    $payment_method->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($order_data->payment_method))
    );
    $leadCustomFieldsValues->add($payment_method);

   // $product_list='';
    $countProducts=0;
    foreach($order_data->products as $p){
        $productField = new TextCustomFieldValuesModel();
        $productField->setFieldId($fieldsProduct[$countProducts]);
        $productField->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($p->uid." размер ". $p->size." в кол. ".$p->quantity." по цене ".$p->price.' ₽'))
    );
    $leadCustomFieldsValues->add($productField);

    $productURLField = new TextCustomFieldValuesModel();
    $productURLField->setFieldId($fieldsURL[$countProducts]);
    $productURLField->setValues(
    (new TextCustomFieldValueCollection())
    ->add((new TextCustomFieldValueModel())->setValue("https://corsocomo.com/".$p->uid)));
    $leadCustomFieldsValues->add($productURLField);
    $countProducts++;
       // $product_list .= " ".$p->uid." размер ". $p->size." в кол. ".$p->quantity." по цене ".$p->price;
    }
    /*$products = new TextCustomFieldValuesModel();
    $products->setFieldId(985831);
    $products->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($product_list))
    );*/
    
    //$leadCustomFieldsValues->add($products);
    $lead = (new LeadModel())
        ->setName($order_data->id)
        ->setPrice($order_data->total)
        /*->setTags(
            (new TagsCollection())
                ->add(
                    (new TagModel())
                        ->setName($externalLead['tag'])
                )
        )*/
        ->setContacts(
            (new ContactsCollection())
                ->add(
                    (new ContactModel())
                        ->setFirstName($order_data->firstname)
                        ->setLastName($order_data->lastname)
                        ->setCustomFieldsValues(
                            (new CustomFieldsValuesCollection())
                                ->add(
                                    (new MultitextCustomFieldValuesModel())
                                        ->setFieldCode('PHONE')
                                        ->setValues(
                                            (new MultitextCustomFieldValueCollection())
                                                ->add(
                                                    (new MultitextCustomFieldValueModel())
                                                        ->setValue($order_data->telephone)
                                                )
                                        )
                                )->add(
                                    (new MultitextCustomFieldValuesModel())
                                        ->setFieldCode('EMAIL')
                                        ->setValues(
                                            (new MultitextCustomFieldValueCollection())
                                                ->add(
                                                    (new MultitextCustomFieldValueModel())
                                                        ->setValue($order_data->email)
                                                )
                                        )
                                )
                        )
                )
        )
        ->setCustomFieldsValues($leadCustomFieldsValues)
        ->setRequestId($order_data->id);

   
        $lead->setMetadata(
            (new FormsMetadata())
                ->setFormId('Сайт CORSOCOMO')
                ->setFormName('Заказ с сайта')
                ->setFormPage('https://corsocomo.com')
               // ->setFormSentAt('2022-12-19 14:54:45')
                
        );
    

    $leadsCollection->add($lead);
//}

//Создадим сделки
try {
    $addedLeadsCollection = $apiClient->leads()->addComplex($leadsCollection);
    foreach($addedLeadsCollection as $l){
        print_r($l->id);
    }
} catch (Throwable $e) {
    report($e);

    return false;
}

}

function addone (){
    $clientId = $_ENV['CLIENT_ID'];
    $clientSecret = $_ENV['CLIENT_SECRET'];
    $redirectUri = $_ENV['CLIENT_REDIRECT_URI'];

    $accessToken =  DB::connection('mysql')->table('tokens')->first();
//dd( $accessToken);
    $token = new AccessToken([
        'access_token' => $accessToken->access_token,
        'refresh_token' => $accessToken->refresh_token,
        'expires' => $accessToken->expires_in,
        'baseDomain' => $accessToken->baseDomain,
    ]);

    $apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

    $accessToken = $token;

    $apiClient->setAccessToken($accessToken)->setAccountBaseDomain($accessToken->getValues()['baseDomain']);

    $leadsService = $apiClient->leads();

    $lead = new LeadModel();
$leadCustomFieldsValues = new CustomFieldsValuesCollection();
$delivery = new TextCustomFieldValuesModel();
$delivery->setFieldId(985827);
$delivery->setValues(
    (new TextCustomFieldValueCollection())
        ->add((new TextCustomFieldValueModel())->setValue('Текст'))
);
$leadCustomFieldsValues->add($delivery);
$lead->setCustomFieldsValues($leadCustomFieldsValues);
$lead->setName('Example');
$lead->setContacts(
    (new ContactsCollection())
        ->add(
            (new ContactModel())
                ->setFirstName($externalLead['contact']['first_name'])
                ->setLastName($externalLead['contact']['last_name'])
                ->setCustomFieldsValues(
                    (new CustomFieldsValuesCollection())
                        ->add(
                            (new MultitextCustomFieldValuesModel())
                                ->setFieldCode('PHONE')
                                ->setValues(
                                    (new MultitextCustomFieldValueCollection())
                                        ->add(
                                            (new MultitextCustomFieldValueModel())
                                                ->setValue($externalLead['contact']['phone'])
                                        )
                                )
                        )->add(
                            (new MultitextCustomFieldValuesModel())
                                ->setFieldCode('EMAIL')
                                ->setValues(
                                    (new MultitextCustomFieldValueCollection())
                                        ->add(
                                            (new MultitextCustomFieldValueModel())
                                                ->setValue($externalLead['contact']['email'])
                                        )
                                )
                        )->add($textCustomFieldValueModel)
                )
        )
                                        );


try {
    $lead = $leadsService->addOne($lead);
} catch (AmoCRMApiException $e) {
    report($e);
    die;
}

}

}
