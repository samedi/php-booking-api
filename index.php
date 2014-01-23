<?php

spl_autoload_register(function ($class) {
    require 'oauth_2.0_client_php/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
});

const CLIENT_ID = "REPLACEME";
$CLIENT_SECRET = "REPLACEME";
$PRACTICE_ID = "REPLACEME";
$REDIRECT_URI = "http://url-of-this/script.php";



$BASE_URI = "https://patient.samedi.de";

// configuration of client credentials
$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

// configuration of service
$configuration = new OAuth2\Service\Configuration(
        $BASE_URI . '/api/auth/v2/authorize',
        $BASE_URI . '/api/auth/v2/token');

// storage class for access token, just implement OAuth2\DataStore interface for
// your own implementation
$dataStore = new OAuth2\DataStore\Session();

$scope = null;

$service = new OAuth2\Service($client, $configuration, $dataStore, $scope);

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'authorize':
            // redirects to authorize endpoint
            $service->authorize();
            break;
        case 'userInfo':
            // calls api endpoint with access token
            $userInfo = json_decode($service->callApiEndpoint($BASE_URI . '/api/booking/v3/user.json'))->user;
            break;
        case 'categories':
            // calls api endpoint with access token
            $categories = json_decode($service->callApiEndpoint($BASE_URI . "/api/booking/v3/event_categories.json?practice_id=${PRACTICE_ID}&client_id=" . CLIENT_ID))->data;
            break;
        case 'eventTypes':
            // calls api endpoint with access token
            $eventTypes = json_decode($service->callApiEndpoint($BASE_URI . "/api/booking/v3/event_types.json?practice_id=${PRACTICE_ID}&client_id=" . CLIENT_ID . '&event_category_id='.$_GET["category_id"]))->data;
            var_dump($eventTypes);
            break;
        case 'times':
            // calls api endpoint with access token
            if (isset($_GET['date'])) {
                $curDate = strtotime($_GET['date']);
            } else {
                $curDate = time();
            }

            $dates = json_decode($service->callApiEndpoint($BASE_URI . "/api/booking/v3/dates.json?practice_id=${PRACTICE_ID}&client_id=" . CLIENT_ID . '&event_category_id='.$_GET["category_id"] . '&event_type_id='.$_GET["event_type_id"] . '&date=' . date('Y-m-d', $curDate)))->data;


            $times = json_decode($service->callApiEndpoint($BASE_URI . "/api/booking/v3/times.json?practice_id=${PRACTICE_ID}&client_id=" . CLIENT_ID . '&event_category_id='.$_GET["category_id"] . '&event_type_id='.$_GET["event_type_id"] . '&date=' . date('Y-m-d', $curDate)))->data;
            break;

        case 'book':
            $result = json_decode($service->callApiEndpoint(
                $BASE_URI . "/api/booking/v3/book.json?practice_id=${PRACTICE_ID}&client_id=" . CLIENT_ID,
                "POST",
                array(),
                array(
                    'event_category_id' => $_GET["category_id"],
                    'event_type_id'     => $_GET["event_type_id"],
                    'starts_at'         => $_GET['starts_at'],
                    'token'             => $_GET['token']
                )
            ));

            break;

    }
}

if (isset($_GET['code'])) {
    // retrieve access token from endpoint
    $service->getAccessToken();
}

$token = $dataStore->retrieveAccessToken();

?>
<html>
    <head>
    </head>
    <body>
        Consumer Key: <input type="text" id="consumer-key" value="<?= $client->getClientKey() ?>" /><br />
        Consumer Secret: <input type="text" id="consumer-secret" value="<?= $client->getClientSecret() ?>" /><br />
        Access Token: <input type="text" id="access-token" value="<?= $token->getAccessToken() ?>" /><br />
        Refresh Token: <input type="text" id="refresh-token" value="<?= $token->getRefreshToken() ?>" /><br />
        LifeTime: <input type="text" id="lifetime" value="<?= $token->getLifeTime() ?>" /><br />
        <br />
        <a href="?action=authorize" id="authorize">authorize</a><br />
        <br />
        <a href="?action=userInfo" id="request-api">fetch user info</a><br />

        <? if (isset($userInfo)) { ?>
        User: <?= $userInfo->full_name ?><br/>
        <a href="?action=categories" >fetch categories</a><br />
        <? } ?>

        <? if (isset($categories)) { ?>
            <? foreach ($categories as $category) { ?>
                <a href="?action=eventTypes&category_id=<?= $category->id ?>"><?= $category->name ?></a><br/>
            <? } ?>
        <? } ?>

        <? if (isset($eventTypes)) { ?>
            <? foreach ($eventTypes as $eventType) { ?>
                <a href="?action=times&category_id=<?= $_GET['category_id'] ?>&event_type_id=<?= $eventType->id ?>"><?= $eventType->name ?></a><br/>
            <? } ?>
        <? } ?>

        <? if (isset($dates)) { ?>
        <h2>Available Dates</h2>
            <? foreach ($dates as $date) { if ($date->available) {?>
                <a href="?action=times&category_id=<?= $_GET['category_id'] ?>&event_type_id=<?= $_GET['event_type_id'] ?>&date=<?= $date->date ?>">
                    <?= $date->date ?>
                </a><br/>
            <? } } ?>

            <h2>Available Times</h2>

            <? foreach ($times as $time) { ?>
                <a href="?action=book&category_id=<?= $_GET['category_id'] ?>&event_type_id=<?= $_GET['event_type_id'] ?>&starts_at=<?= $time->time ?>&token=<?= $time->token ?>">
                    <?= date("H:i", strtotime($time->time)) ?>
                </a><br/>
            <? } ?>

        <? } ?>

        <? if (isset($result)) { ?>
            <h1>Ergebnis ihrer Terminbuchung</h1>

            <? if (isset($result->error)) { ?>
            Fehler: <?= $result->error ?>
            <? } else { ?>
            Termin gebucht!<br/>
            Termin-Info: <?= $result->data->name ?> <?= $result->data->starts_at ?><br/>
            <? } ?>
        <? } ?>

    </body>
</html>
