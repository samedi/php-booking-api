<?php

define('__ROOT__',dirname(__FILE__));

require_once(str_replace('\\', DIRECTORY_SEPARATOR, __ROOT__.'\Requests-1.6.0\library\Requests.php'));


spl_autoload_register( function($class){
    if(file_exists('oauth_2.0_client_php/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php')) {
        require 'oauth_2.0_client_php/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    }
    else
    {
        Requests::register_autoloader();
    }   
});

$client_id = "REPLACEME";
$client_secret = "REPLACEME";
$practice_id = "REPLACEME";
$redirect_uri = "REPLACEME";

$base_uri = "https://patient.samedi.de";

// configuration of client credentials
$client = new OAuth2\Client($client_id, $client_secret, $redirect_uri);

// configuration of service
$configuration = new OAuth2\Service\Configuration(
        $base_uri . '/api/auth/v2/authorize',
        $base_uri . '/api/auth/v2/token');

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
            $userInfo = json_decode($service->callApiEndpoint($base_uri . '/api/booking/v3/user.json'))->user;
            break;
        case 'categories':
            // calls api endpoint with access token
            $categories = json_decode($service->callApiEndpoint($base_uri . "/api/booking/v3/event_categories.json?practice_id=${practice_id}&client_id=" . $client_id))->data;
            break;
        case 'courses-without-oauth':
            // calls api endpoint with access token
            $headers = array('Accept' => 'application/json');
            $courses_without_oauth = Requests::get($base_uri . "/api/booking/v3/courses.json?practice_id=${practice_id}&client_id=" . $client_id, $headers);
            $courses_wo = json_decode($courses_without_oauth->body)->data;
            break;
        case 'courses-with-oauth':
            // calls api endpoint with access token
            $courses_with_oauth = json_decode($service->callApiEndpoint($base_uri . "/api/booking/v3/courses.json?practice_id=${practice_id}&client_id=" . $client_id))->data;
            break;
            
        case 'eventTypes':
            // calls api endpoint with access token
            $eventTypes = json_decode($service->callApiEndpoint($base_uri . "/api/booking/v3/event_types.json?practice_id=${practice_id}&client_id=" . $client_id . '&event_category_id='.$_GET["category_id"]))->data;
            var_dump($eventTypes);
            break;
        case 'times':
            // calls api endpoint with access token
            if (isset($_GET['date'])) {
                $curDate = strtotime($_GET['date']);
            } else {
                $curDate = time();
            }

            $dates = json_decode($service->callApiEndpoint($base_uri . "/api/booking/v3/dates.json?practice_id=${practice_id}&client_id=" . $client_id . '&event_category_id='.$_GET["category_id"] . '&event_type_id='.$_GET["event_type_id"] . '&date=' . date('Y-m-d', $curDate)))->data;


            $times = json_decode($service->callApiEndpoint($base_uri . "/api/booking/v3/times.json?practice_id=${practice_id}&client_id=" . $client_id . '&event_category_id='.$_GET["category_id"] . '&event_type_id='.$_GET["event_type_id"] . '&date=' . date('Y-m-d', $curDate)))->data;
            break;

        case 'book':
            $result = json_decode($service->callApiEndpoint(
                $base_uri . "/api/booking/v3/book.json?practice_id=${practice_id}&client_id=" . $client_id,
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

        case 'book-course-with-oauth':
            $result = json_decode($service->callApiEndpoint(
                $base_uri . "/api/booking/v3/courses/" . $_GET['course_id'] . "/participation?",
                "POST",
                array(),
                array()
            ));
            break;

        case 'book-course':
            $result = json_decode(Requests::post(
                $base_uri . "/api/booking/v3/courses/" . $_GET['course_id'] . "/participation?practice_id=${practice_id}&client_id=" . $client_id, array(),
                    array(
                    'attendant' => array(
                        'data' => array(
                            'last_name' => $_GET["last_name"],
                            'first_name' => $_GET["first_name"],
                            'email' => $_GET["email"]
                                )
                            )
                        ))->body);
                print_r($result);
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

        <?php if (isset($userInfo)) { ?>
        User: <?= $userInfo->full_name ?><br/>
        <a href="?action=categories" >fetch categories</a><br />
        <?php } ?>

        <?php if (isset($userInfo)) { ?>
        <a href="?action=courses-with-oauth">fetch courses with oauth</a><br />
        <?php } ?>

        <?php if (isset($categories)) { ?>
            <?php foreach ($categories as $category) { ?>
                <a href="?action=eventTypes&category_id=<?= $category->id ?>"><?= $category->name ?></a><br/>
            <?php } ?>
        <?php } ?>
        
        <a href="?action=courses-without-oauth" >fetch courses without oauth</a><br /><br />
        
        <?php if (isset($courses_wo)) 
            { ?>
            <form action="index.php" method="get">
                
                Last Name:  <input type="text" name="last_name"/><br />
                First Name: <input type="text" name="first_name"/><br />
                E-Mail: <input type="text" name="email"/><br /><br />
                <?php foreach ($courses_wo as $course) { ?>
                    <input type="radio" name="course_id" value="<?= $course->id ?>"><?=$course->name?></input> 
                    <p>the course <?= $course->name ?> has <?= $course->open_positions ?> of <?= $course->maximum_participants ?> slots available</p>
                        <?php foreach ($course->appointments as $appointment) { ?>
                            <p><?= $appointment->starts_at ?></p>
                        <?php } ?>
                    <?php } ?>
                    <input type="hidden" name="action" value="book-course">
                    <input type="submit" value="book selected course">
                </form>
            <?php } ?>

        <?php if (isset($courses_with_oauth)) 
            { ?>
            <form action="index.php" method="get">
                <?php foreach ($courses_with_oauth as $course) { ?>
                    <input type="radio" name="course_id" value="<?= $course->id ?>"><?=$course->name?></input> 
                    <p>the course <?= $course->name ?> has <?= $course->open_positions ?> of <?= $course->maximum_participants ?> slots available</p>
                        <?php foreach ($course->appointments as $appointment) { ?>
                            <p><?= $appointment->starts_at ?></p>
                        <?php } ?>
                    <?php } ?>
                    <input type="hidden" name="action" value="book-course-with-oauth">
                    <input type="submit" value="book selected course with OAuth">
                </form>
            <?php } ?>

        
        <?php if (isset($eventTypes)) { ?>
            <?php foreach ($eventTypes as $eventType) { ?>
                <a href="?action=times&category_id=<?= $_GET['category_id'] ?>&event_type_id=<?= $eventType->id ?>"><?= $eventType->name ?></a><br/>
            <?php } ?>
        <?php } ?>

        <?php if (isset($dates)) { ?>
        <h2>Available Dates</h2>
            <?php foreach ($dates as $date) { if ($date->available) {?>
                <a href="?action=times&category_id=<?= $_GET['category_id'] ?>&event_type_id=<?= $_GET['event_type_id'] ?>&date=<?= $date->date ?>">
                    <?= $date->date ?>
                </a><br/>
            <?php } } ?>

            <h2>Available Times</h2>

            <?php foreach ($times as $time) { ?>
                <a href="?action=book&category_id=<?= $_GET['category_id'] ?>&event_type_id=<?= $_GET['event_type_id'] ?>&starts_at=<?= $time->time ?>&token=<?= $time->token ?>">
                    <?= date("H:i", strtotime($time->time)) ?>
                </a><br/>
            <?php } ?>

        <?php } ?>

        <?php if (isset($result)) { ?>
            <h1>Ergebnis ihrer Terminbuchung</h1>

            <?php if (isset($result->error)) { ?>
            Fehler: <?= $result->error ?>
            <?php } else { 
                        if (!array_key_exists ( 'success' , $result )) { ?>
                            Termin gebucht!<br/>
                            Termin-Info: <?= $result->data->name ?> <?= $result->data->starts_at ?><br/>
                            <?php       } else { ?>
                            Kurs wurde erfolgreich gebucht
                            <?php }
                    }
                } ?>

    </body>
</html>
