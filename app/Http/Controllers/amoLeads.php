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
function getauthcode() {

$subdomain = 'kirillovcorsocomocom'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
    'client_id' => '51663aab-2fae-42df-81fb-f24496dc0380',
    'client_secret' => '1GE4bWFWv6AUhuHw2zUIob5V08WlryXfBRzf0yS9UMX5Bf235UdGhle3vpbz30He',
    'grant_type' => 'authorization_code',
    'code' => 'def5020038a601e59ff292240c47a1fa3a582d74ce63e156adf6b6f9003bd683f431d0ca6d5a3f6430006ed2c772be1d582a55386bd0b2bbfce0505d2469ceab4ee9e983cc958aacfb4fd2d5149e4b4cb610da8c49636511d7a52e9fdca8bda0deb8f66b97d0b36d133428df11e24fb374c19137718cdde6d82ee0cc9e48520722781fdaa83cd68afec09c38efffa46c18ed5082a070f27d033ef3b7131337171bde8164ffbc9bf677617e014893c77c35649172140fb9ef9408cbbfdbe18c035311d0a69c7c96421b3f56ec2edde02f32732795b93eac0d109374f9223e31ed515ab582b1c4a5e0fe6178f815f0053ee2d73a899213d9c600f7d6a1f475d074bedf12d3be0f94bc5d2c422753cc9442e6e67bd5c11adf19d20265131042ff5dc6b9f40f4072258126c3c518348f381cf87b93e04210c11a62acde2b7f8d5355634e2ffbabccb0e0d31b7b0a842a53fff3e2a79cb22505443e9ca509c294cac10d305a0b41fb1da68afa67e466c851057e2c32373fb635e933ea84579552c215c661116a66ab1cafbd0c111877516e29aafcf02d72f285ee811290f47a27ce53081849312b43b60aa4cd2c7ada3e6018717ea16cd1b737d41ada35b0d50249a83cfffa0967ba3ca0c20b3a67c84d425a8443ef2693086c54773ab39dd8b7ca6c9d8b72b8',
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

    $o = (object) array(
        'id' => $order->order_id,
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
    $delivery = new TextCustomFieldValuesModel();
    $delivery->setFieldId(985827);
    $delivery->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($order_data->city.' '.$order_data->address1.' '.$order_data->address2.' '.$order_data->metro))
    );
    $product_list='';
    foreach($order_data->products as $p){
        $product_list .= " ".$p->uid." размер ". $p->size." в кол. ".$p->quantity." по цене ".$p->price;
    }
    $products = new TextCustomFieldValuesModel();
    $products->setFieldId(985831);
    $products->setValues(
        (new TextCustomFieldValueCollection())
            ->add((new TextCustomFieldValueModel())->setValue($product_list))
    );
    $leadCustomFieldsValues->add($delivery);
    $leadCustomFieldsValues->add($products);
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
