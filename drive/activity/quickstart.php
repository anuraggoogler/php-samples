<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
// [START drive_activity_quickstart]
require_once __DIR__ . '/vendor/autoload.php';


define('APPLICATION_NAME', 'G Suite Activity API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/appsactivity-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/appsactivity-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Appsactivity::ACTIVITY,
  Google_Service_Appsactivity::DRIVE_METADATA_READONLY)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Appsactivity($client);

// Print the recent activity in your Google Drive.
$optParams = array(
  'source' => 'drive.google.com',
  'drive.ancestorId' => 'root',
  'pageSize' => 10,
);
$results = $service->activities->listActivities($optParams);

if (count($results->getActivities()) == 0) {
  print "No activity.\n";
} else {
  print "Recent activity:\n";
  foreach ($results->getActivities() as $activity) {
    $event = $activity->getCombinedEvent();
    $user = $event->getUser();
    $target = $event->getTarget();
    if (empty($user) || empty($target)) {
      continue;
    }
    $time = date(DateTime::RFC3339, $event->getEventTimeMillis() / 1000);
    printf("%s: %s, %s, %s (%s)\n", $time, $user->getName(),
        $event->getPrimaryEventType(), $target->getName(),
        $target->getMimeType());
  }
}
// [END drive_activity_quickstart]
