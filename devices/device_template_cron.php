<?php

include './vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Текущий скрипт предназначен для отправки телеметрии устройства.
 * Запускается единажды и работает постоянно отсылая новые данные с определенной периодичностью.
 */

/**
 * Не ограничивается время работы устройства.
 */
set_time_limit(0);

/**
 * Аргументом во время запуска является его уникальный пин.
 * Сейчас пины выдаются устройствам от 123450 и на повышение для пользовательских устройств и от 223450 для устройств сервисного центра.
 */
$pin = $argv[1];

/**
 * Устанавливается соединение с RabbitMQ сервером.
 * Адресом локально указывается имя docker контейнера, для глобального использования указываеся адрес сервер.
 * Текущее значение 194.182.85.89.
 */
$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();

/**
 * Для отчетности используется уникальная очередь сервера.
 * Ее всегда слушает сервер и через нее ожидает телеметрию устройства.
 */
$channel->queue_declare(
    'reports',
    false,
    false,
    false,
    false
);

/**
 * Массив тестовых координат необходимый для иммитации перемещения автомобиля.
 */
$routes = [
    json_decode("[[55.7786751,37.6639101],[55.7781136,37.6625812],[55.7771051,37.6604033],[55.7766867,37.6594377],[55.7764292,37.6589012],[55.7762468,37.6584506],[55.7759893,37.6575387],[55.7753348,37.654953],[55.7750344,37.6536977],[55.7747447,37.6527643],[55.7747233,37.6523244],[55.7747769,37.6519275],[55.7747447,37.6516271],[55.774616,37.6512408],[55.7742405,37.6499534],[55.7738328,37.6487625],[55.7736611,37.648344],[55.7734251,37.6478505],[55.7726848,37.6467347],[55.772481,37.6464558],[55.7721591,37.6460052],[55.7707751,37.644192],[55.770421,37.6436448],[55.7709897,37.6417458],[55.7720947,37.6382375],[55.7722342,37.6375937],[55.7725346,37.6355445],[55.7727921,37.6329696],[55.772835,37.6323473],[55.7734251,37.628324],[55.7736182,37.626586],[55.7736611,37.626425],[55.7737577,37.6256633],[55.7738543,37.6242578],[55.7738864,37.6225412],[55.7738864,37.621479],[55.7740045,37.6213717],[55.7741654,37.6211572],[55.7745838,37.6206744],[55.7746053,37.6205242],[55.7746053,37.6203847],[55.7745624,37.6203096],[55.7744658,37.6202345],[55.7740259,37.620331],[55.773865,37.6204383],[55.7737577,37.6206315],[55.773586,37.6208782],[55.7735538,37.6209962],[55.7732856,37.6210499],[55.7729852,37.6210821],[55.7705634,37.6210949]]"),
    json_decode("[[55.7496285,37.6322937],[55.7495964,37.6322937],[55.7495213,37.6323581],[55.7493925,37.6331949],[55.7491994,37.6340747],[55.7479119,37.6387954],[55.7477295,37.6393747],[55.7475471,37.6398039],[55.7474935,37.6400292],[55.7474077,37.6401258],[55.7470965,37.6405549],[55.7469034,37.6407695],[55.7470751,37.6410055],[55.7475901,37.6415956],[55.7477188,37.6417673],[55.7478905,37.6420677],[55.7479548,37.6422286],[55.7480621,37.6426148],[55.7484055,37.644546],[55.7485986,37.6455224],[55.7486737,37.6458657],[55.7489848,37.6475716],[55.7491136,37.6484191],[55.7494676,37.6527321],[55.7495749,37.6537621],[55.7495964,37.6541376],[55.7496929,37.654835],[55.7498538,37.6557255],[55.749886,37.6559722],[55.7501543,37.6573026],[55.7502186,37.6579571],[55.7502294,37.658236],[55.7501543,37.6600277],[55.7500684,37.6611328],[55.7499826,37.6617122],[55.7498646,37.662195],[55.7498002,37.6623666],[55.7497251,37.6625597],[55.7495213,37.6629245],[55.749253,37.6632679],[55.7486522,37.663976],[55.748105,37.6646948],[55.7479656,37.6648235],[55.7477617,37.6651347],[55.7476759,37.6656067],[55.7476544,37.6663256],[55.7476759,37.6669908],[55.7477403,37.6676667],[55.7477188,37.6679778],[55.7476544,37.6683748],[55.7470858,37.6688898],[55.7470107,37.668879],[55.7469356,37.6689005],[55.7467854,37.6690078],[55.7466137,37.6691902],[55.7464957,37.6694047],[55.7464635,37.6695335],[55.7465816,37.670188],[55.7466137,37.6705527],[55.7469249,37.6748228],[55.7469249,37.6754022],[55.7468927,37.6763248],[55.7465923,37.6795435],[55.7464099,37.6797795],[55.7462597,37.6799405]]"),
    json_decode("[[55.7258105,37.6228952],[55.7258749,37.623378],[55.7261753,37.6248157],[55.7263148,37.6253629],[55.7266796,37.6251376],[55.7267761,37.6250947],[55.7270229,37.6250303],[55.7271624,37.6249766],[55.7272911,37.6249552],[55.7275486,37.625041],[55.7287395,37.6253736],[55.729115,37.6253951],[55.7294798,37.6254809],[55.7296836,37.6254916],[55.7299519,37.6254487],[55.7299089,37.6245797],[55.7299197,37.6238394],[55.7302308,37.6145911],[55.730263,37.6128316],[55.7302415,37.6122952],[55.7310355,37.6122522],[55.731926,37.6122844],[55.7323122,37.6123381],[55.7327735,37.6124561],[55.7333851,37.6126599],[55.7341683,37.6129496],[55.7349086,37.6132822],[55.735563,37.6136148],[55.7359171,37.6138294],[55.736711,37.6143551],[55.7367647,37.6149881],[55.7369363,37.6163936],[55.7370007,37.6168656],[55.7370329,37.6170051]]"),
    json_decode("[[55.7748199,37.6038408],[55.774895,37.604506],[55.7748199,37.6048708],[55.7745945,37.6049459],[55.7745731,37.6048064],[55.7740796,37.6049352],[55.7742834,37.6052248],[55.7744765,37.6056004],[55.7747018,37.6054823],[55.774895,37.605536],[55.7749915,37.6055253],[55.7750452,37.6055896],[55.7751203,37.6057935],[55.7751954,37.6063836],[55.7752597,37.6067483],[55.7753026,37.6068127],[55.775485,37.6067698],[55.7755601,37.6073492],[55.7755709,37.6077461],[55.7746267,37.6082397],[55.773468,37.6087868],[55.7731998,37.6089907],[55.7731247,37.608186],[55.7730496,37.6075959],[55.7729101,37.6065767],[55.7726526,37.6049781],[55.7725132,37.6043665],[55.7715797,37.6012123],[55.7711613,37.5998175],[55.7705498,37.5982404],[55.7702494,37.5975752],[55.7697344,37.5965238],[55.769155,37.5954616],[55.7684469,37.5943136],[55.7673526,37.5926507],[55.7669127,37.5920177],[55.7666016,37.5915992],[55.7654965,37.5900114],[55.7648957,37.5892389],[55.7644022,37.5886703],[55.7639515,37.5881982],[55.7632864,37.5875974],[55.7624924,37.5869429],[55.761795,37.5863957],[55.7603681,37.5853872],[55.7594025,37.5847864],[55.7589626,37.5845397],[55.7590377,37.5843251],[55.7591128,37.5840354],[55.7594347,37.5824368],[55.7595205,37.5821686],[55.760293,37.5807202],[55.760529,37.5802374],[55.7607007,37.5793576],[55.7607436,37.5790572],[55.7607543,37.5787783],[55.7607758,37.5781238],[55.760808,37.5776839],[55.7609582,37.5761604],[55.7612371,37.573843],[55.7617092,37.5704634],[55.7621169,37.5677598],[55.7624173,37.5659466],[55.7625031,37.5657749],[55.7625246,37.5656998],[55.7626748,37.5647986],[55.7628465,37.5640047],[55.7629967,37.5634146],[55.7628679,37.5632215]]"),
    json_decode("[[55.7569027,37.6151276],[55.7570314,37.614913],[55.7570958,37.6148915],[55.7571924,37.6147628],[55.7573533,37.6144302],[55.7578468,37.6135182],[55.7581687,37.6130891],[55.758909,37.6123166],[55.7591128,37.6121342],[55.7592845,37.6119304],[55.7591772,37.611748],[55.758909,37.6119947],[55.7584155,37.6125956],[55.7582223,37.6120698],[55.7577395,37.6112223],[55.7576001,37.6110077],[55.7558191,37.6087332],[55.7557011,37.6086152],[55.7554007,37.6095378],[55.7545209,37.6085615],[55.7542741,37.6083791],[55.7526755,37.607435],[55.7528901,37.6034975],[55.7529974,37.6010621],[55.7529974,37.6003969],[55.7530081,37.5992703],[55.7529867,37.5984657],[55.7528687,37.5962985],[55.7527399,37.5934875],[55.7526755,37.5904191],[55.7526541,37.5755918],[55.7527292,37.5753772],[55.7528472,37.5751305],[55.7529116,37.5750554],[55.7530189,37.5749803],[55.7532549,37.5749373],[55.754199,37.5758171],[55.7551968,37.5767827],[55.7563555,37.5778234],[55.7567954,37.5781238],[55.7570207,37.5782311],[55.7575035,37.5784135],[55.7577825,37.5784993],[55.7581794,37.5785744],[55.7580936,37.5792503],[55.7579648,37.5799263],[55.7579112,37.5803983],[55.7578146,37.5809562],[55.7577395,37.5811493],[55.7574821,37.5815892],[55.7574069,37.5818789],[55.7572889,37.5825655],[55.7573104,37.5833702],[55.7572997,37.5838208],[55.7572675,37.5841212],[55.7563663,37.5838959],[55.7560551,37.5839388],[55.7548428,37.5836706],[55.7521106,37.5831906]]"),
    json_decode("[[55.7202509,37.584366],[55.7194269,37.5834024],[55.7186222,37.5823832],[55.718236,37.5818682],[55.7179463,37.5814497],[55.7170129,37.5800335],[55.7154572,37.5773942],[55.7160258,37.5760746],[55.7165837,37.5748837],[55.7175279,37.5727808],[55.7177424,37.5722873],[55.7181931,37.5711501],[55.718354,37.5706351],[55.7184076,37.5703347],[55.718472,37.570163],[55.7198882,37.5655925],[55.7204783,37.5638545],[55.7205319,37.5638115],[55.7205856,37.5637364],[55.72065,37.5636077],[55.7209826,37.5626421],[55.7211113,37.5627923],[55.7214975,37.563554],[55.7219911,37.5646377],[55.7224417,37.5637364],[55.7221842,37.5631249]]"),
    json_decode("[[55.7845178,37.682267],[55.7834458,37.6873219],[55.7833707,37.6878369],[55.7833815,37.6879764],[55.7834673,37.6882017],[55.7839286,37.6886952],[55.7835209,37.6918495],[55.7831454,37.6945424],[55.7830381,37.6947999],[55.7827377,37.695905],[55.7826519,37.6963127],[55.782609,37.6966774],[55.7825983,37.6974177],[55.7826412,37.6989305],[55.7826412,37.7002394],[55.7826626,37.7014625],[55.7825983,37.7016234],[55.7825661,37.7016449],[55.7824802,37.7015913],[55.7824588,37.701484],[55.7824373,37.7011728],[55.7824266,37.6997352],[55.7827699,37.699703],[55.7835209,37.6995528],[55.7842827,37.6994348],[55.7862246,37.6990485],[55.7865787,37.6990163],[55.786761,37.6990271],[55.7869434,37.6990592],[55.7878554,37.6992846],[55.7882416,37.699424],[55.7884133,37.6995099],[55.7885742,37.6996171],[55.7887781,37.6997888],[55.7889497,37.6999712],[55.7890999,37.7001643],[55.7892823,37.7004433],[55.789572,37.700969],[55.7905483,37.7029002],[55.7916749,37.705003],[55.7918787,37.705282],[55.7921147,37.705518],[55.7922328,37.7056146],[55.7923937,37.7057111],[55.7926834,37.7058291],[55.7928121,37.7058613],[55.7929838,37.7058721],[55.793134,37.7058399],[55.7934022,37.7057433],[55.7935309,37.7056789],[55.7937455,37.7055395],[55.793885,37.7054322],[55.7943249,37.7050138],[55.7948828,37.7044237],[55.7951617,37.7041018],[55.7962561,37.7027822],[55.7969213,37.7020097],[55.7971251,37.7036726],[55.7971895,37.7039516],[55.7972646,37.7040696],[55.7973289,37.7040911],[55.7974041,37.7040803],[55.7977045,37.7039838],[55.798949,37.7035117],[55.8002901,37.7030396],[55.8006763,37.7054858],[55.8009017,37.7067733],[55.800966,37.7072239],[55.8011484,37.7081895],[55.8008051,37.7083719],[55.7988632,37.7092946],[55.7981336,37.7096593],[55.7979298,37.7097452],[55.7974255,37.7098739],[55.7967389,37.7100241],[55.796396,37.7100825]]"),
    json_decode("[[55.7672431,37.8950082],[55.767417,37.8964269],[55.7676315,37.8979397],[55.7665586,37.8983581],[55.7656896,37.8919852],[55.7651639,37.8880048],[55.7647133,37.8883588],[55.764091,37.8888953],[55.7634366,37.8895819],[55.7630396,37.8900433],[55.7620633,37.8913307],[55.761838,37.8900647],[55.7617843,37.8896892],[55.7615697,37.8886271],[55.7612908,37.8870392],[55.7611513,37.8863418],[55.7610655,37.8857839],[55.7610548,37.8851831],[55.7610977,37.8837562],[55.7596171,37.8839493],[55.7589841,37.884239],[55.7581794,37.8850651],[55.7570851,37.8865349],[55.756613,37.8869963],[55.7550037,37.888037],[55.7546067,37.888273],[55.7545745,37.8881979],[55.7545209,37.888155],[55.7544565,37.8881443],[55.7543707,37.8881764],[55.7542956,37.8883052],[55.7542849,37.8884125],[55.7540596,37.8886485],[55.7536411,37.8888094],[55.7531369,37.8890347],[55.752461,37.889539],[55.7522249,37.8896999],[55.7521176,37.8897536],[55.7516026,37.889818],[55.7514846,37.8885734],[55.7514524,37.8883588],[55.7513237,37.8879189],[55.7510662,37.8874362],[55.7507229,37.8868568],[55.7501006,37.8858912],[55.7499504,37.8856337],[55.7497358,37.8851724],[55.74965,37.8848827],[55.749135,37.8822756],[55.748899,37.8812027],[55.7483196,37.8782845],[55.7476115,37.8745508],[55.747515,37.8741109],[55.746839,37.870506],[55.7456589,37.8644335],[55.7454658,37.8631139],[55.7453048,37.8618693],[55.7451653,37.8603888],[55.7450259,37.858125],[55.7448971,37.8542948],[55.7448435,37.8533399],[55.7445753,37.850765],[55.7444465,37.8491449],[55.7443821,37.8479755],[55.7444358,37.8473854],[55.7444894,37.8469884],[55.7446289,37.8462696],[55.7447147,37.845937],[55.7449722,37.8451967],[55.7460022,37.8432655],[55.7463241,37.8429008],[55.7466459,37.8425789],[55.746839,37.8424394],[55.7472253,37.8422034],[55.7551968,37.8426325],[55.7552826,37.8428042],[55.7553899,37.8431368],[55.7559478,37.8459907],[55.7560551,37.8464735],[55.7564306,37.8464735],[55.760808,37.8466773],[55.7614303,37.8467309],[55.7616341,37.8467953],[55.761795,37.8469133],[55.7622993,37.8475463],[55.763104,37.8486836],[55.7642305,37.8501964],[55.7648957,37.8511298],[55.7652283,37.8516448],[55.7644451,37.8530824],[55.7627177,37.8563333],[55.7623125,37.8562877]]"),
    json_decode("[[55.7085926,37.7306804],[55.7089126,37.7306449],[55.7092559,37.7306449],[55.7095993,37.7306235],[55.7098782,37.7305698],[55.7100391,37.7304411],[55.7111979,37.7302051],[55.7113051,37.7301943],[55.7118309,37.7302158],[55.7122922,37.7301836],[55.7126892,37.7300441],[55.7129788,37.7299047],[55.7131934,37.7296793],[55.7136118,37.7290249],[55.7138693,37.7285421],[55.7139337,37.7285099],[55.7147491,37.7270508],[55.7150924,37.7264714],[55.7153177,37.7261066],[55.7156396,37.7256346],[55.7157469,37.7257204],[55.7163262,37.7260745],[55.7166159,37.7262247],[55.7188582,37.7275121],[55.7190406,37.7276516],[55.719105,37.7277374],[55.7192123,37.7279627],[55.7192981,37.7283382],[55.7195663,37.7300763],[55.719738,37.7310526],[55.7201028,37.7329195],[55.7201457,37.7332842],[55.7211542,37.7333486],[55.72227,37.7334452],[55.7225597,37.7335095],[55.7226348,37.7335417],[55.7228386,37.733649],[55.7229674,37.7337456],[55.723182,37.7339816],[55.7232678,37.7341211],[55.7233429,37.7342713],[55.7234716,37.7346468],[55.7241261,37.7372324],[55.7241797,37.7373183],[55.7242656,37.7373934],[55.7248449,37.7375758],[55.7250595,37.7376938],[55.7252312,37.7378654],[55.7253492,37.7381444],[55.7254028,37.7383053],[55.7254887,37.7387667],[55.7257462,37.740494],[55.7260895,37.742511],[55.7263148,37.7437019],[55.7266581,37.7463949],[55.7270551,37.7484012],[55.7277524,37.7527356],[55.7278168,37.7534544],[55.7277846,37.7537978],[55.7277417,37.7540338],[55.7276988,37.7541733],[55.7273769,37.7552676],[55.7270229,37.7569413],[55.7269478,37.7571988],[55.7262397,37.7592373],[55.7263899,37.759409],[55.7244372,37.7650845],[55.7242334,37.7657175],[55.7229888,37.7692366],[55.7227635,37.7699447],[55.7218623,37.772584],[55.7210147,37.7749979],[55.72065,37.7760923],[55.7203281,37.7770686],[55.7198989,37.7785385],[55.7194269,37.7803195],[55.7189548,37.7824652],[55.718869,37.7829266],[55.7185042,37.7852547],[55.7179248,37.7887201],[55.7173026,37.7926576],[55.7166588,37.7965093],[55.7164013,37.7982152],[55.7164228,37.7984512],[55.7158649,37.8018951],[55.7156718,37.8028286],[55.7153928,37.8040195],[55.7131505,37.8127313],[55.7130325,37.8127956],[55.7129252,37.8129137],[55.7127857,37.8133321],[55.7124424,37.8145444],[55.7120562,37.8141153],[55.711745,37.8152633],[55.7113051,37.8149092],[55.7102323,37.8150058],[55.7101786,37.8152418],[55.7101142,37.8153491],[55.7099962,37.8154135]]"),
    json_decode("[[55.6929588,37.5360346],[55.6929696,37.5363886],[55.6929374,37.5366139],[55.6893647,37.5428152],[55.6893218,37.5429118],[55.6882274,37.5448108],[55.6877446,37.5456798],[55.6838393,37.5524497],[55.6833994,37.5532007],[55.6827021,37.5544453],[55.6824231,37.5548637],[55.6820047,37.5552499],[55.6819081,37.5553679],[55.681479,37.5560546],[55.6805348,37.5577283],[55.6798911,37.5588012],[55.6785178,37.561183],[55.6783783,37.5613976],[55.6779492,37.5622559],[55.6778097,37.5626206],[55.6771338,37.5619125],[55.6744516,37.5590158],[55.6729174,37.557385],[55.6719947,37.556355],[55.6710398,37.5553465],[55.6701601,37.5544453],[55.6700313,37.5542951],[55.6698704,37.5541449],[55.6679821,37.5520849],[55.664506,37.5483727],[55.6616735,37.545315],[55.6608152,37.5444245],[55.660311,37.5438666],[55.6602252,37.5437915],[55.6584227,37.5418603],[55.6561375,37.5393927],[55.6559658,37.5388992],[55.6559443,37.5384915],[55.6561053,37.5376332],[55.6562448,37.5366247],[55.6562555,37.5363886],[55.6567919,37.5310028],[55.6567061,37.5309062],[55.6563735,37.5344574],[55.6561482,37.5366247],[55.6559551,37.5379765],[55.6557512,37.538985],[55.6557083,37.5391245],[55.6574249,37.5409698],[55.658015,37.5416243],[55.6580257,37.5417852],[55.658015,37.5420642],[55.6571031,37.5446928],[55.6560946,37.5435555],[55.6562989,37.5422737]]"),
];

/**
 * Запускается постоянная отправка собщений на сервер.
 * Сообщения отправляются с периодичностью в 30 секунд.
 */
while (true) {
    /**
     * Далее генерируются тестовые данные с определенной вероятностью содержащие ошибки.
     */

    $TP = 0;
    if(rand(0, 1000) > 950) {
        $TP = 1;
    }

    $OL = 0;
    if(rand(0, 1000) > 950) {
        $OL = 1;
    }

    $FL = 0;
    if(rand(0, 1000) > 950) {
        $FL = 1;
    }

    $position = 0;

    if(isset($routes[(int) $pin[5]][$position])) {
        $position++;
    } else {
        $position = 0;
    }

    $message = new AMQPMessage(json_encode([
        'pin' => $pin,
        'vin' => implode('', array_reverse(str_split($pin))),
        'TP' => $TP,
        'CL' => rand(0, 200),
        'OL' => $OL,
        'FL' => $FL,
        'BV' => rand(0, 100),
        'miliage' => rand(34764, 137654),
        'latitude' => $routes[(int) $pin[5]][$position][0],
        'longitude' => $routes[(int) $pin[5]][$position][1]
    ]), array('delivery_mode' => 2));
    $channel->basic_publish($message, '', 'reports');
    sleep(30);
}

/**
 * Закрывается соединение в случае закрытия очереди или возникшей проблемы.
 * На практике никогда не происходило.
 */
$channel->close();
try {
    $connection->close();
} catch (Exception $e) {}
