<?php

require 'vendor/autoload.php';

use Thunder\SimilarWebApi\Client;
use Thunder\SimilarWebApi\ClientFacade;


// Config
$yourUserKey    = 'ca1476940ddbdb09b667b62dc5f3cbd4';
$desiredFormat  = 'json';
$domain         = filter_var($_POST['domain'], FILTER_SANITIZE_URL);
$period         = 'monthly'; // daily, weekly, monthly *Default: monthly
$start          = date('m-Y', strtotime('first day of previous month')); // Start Month (MM-YYYY) *Required
$end            = date('m-Y', strtotime('first day of previous month')); // End Month (MM-YYYY) *Required
$main           = false; // false/true. Get metrics on the Main Domain only (i.e. not including subdomains). *Default: false
$topscore       = filter_var($_POST['top'], FILTER_SANITIZE_URL) / 100;

if($_GET['debug'] == 'off') {
  $debug        = false;
} else {
  $debug        = true;
}

// display month
if($period == 'monthly' && $start == $end){
  $month = date('M Y', strtotime('01-'.$start));
}

// if the domain is set generate the client object, get the similar websites and then loop through them to create the data object
if($domain){

   // create client object
  $client = new Client($yourUserKey, $desiredFormat);
  $clientFacade = new ClientFacade($client);
  $data = array();

  // get the data for the primary domain and push into the data array
  $data[$domain] = getStats($domain);


  // get 5 similar websites, retreive the data for each domain and push into data array - Now removed
  /*$similarWebsites = getSimilarSites($domain);
  foreach($similarWebsites AS $matchdomain => $matchscore){

    $data[$matchdomain] = getStats($matchdomain);

  }*/

  // get the values from the other inputs if set
  $i = 1;
  while ($i <= 5) {
      if($_POST['domain'.$i] != '') {
        $comparedomain =  filter_var($_POST['domain'.$i], FILTER_SANITIZE_URL);
        $data[$comparedomain] = getStats($comparedomain);
      }
      $i++;
  };

  $data = calculateScores($data);

  $hits = sizeof($data) * 6;
  $cost = 0.02 * $hits;

  session_start();
  $_SESSION['data'] = $data;
  $_SESSION['month'] = $month;

}

// wrapper function to get the stats for the domain
function getStats($domain){

  $data = array();

  /** Stats to display **/

  /*
  --- TrafficPro API --- 1 credit
  - Estimated number of visits for the domain
  */
  $data['estimatedVisits'] = getEstimatedVisits($domain);

  /*
  --- Traffic API --- 1 credit
  - Global Rank
  - Country Rank
  - % Global Traffic
  - % Traffic Sources
  */
  $data['trafficData'] = getTrafficData($domain);


  /*
  --- EngagementPageViews API --- 1 credit
  - Average Page Views
  */
  $data['pageViews'] = getPageViews($domain);

  /*
  --- EngagementVisitDuration API --- 1 credit
  - Average Visit Duration
  */
  $data['visitDuration'] = getVisitDuration($domain);

  /*
  --- EngagementBounceRate API --- 1 credit
  - % Bounce Rate
  */
  $data['bounceRate'] = getBounceRate($domain);

  /*
  --- SocialReferrals API --- 1 credit
  - % of Traffic for each Social Network
  */
  $data['socialReferrals'] = getSocialReferrals($domain);

  return($data);

}

function getEstimatedVisits($domain) {

  global $debug;

  if($debug){

    $estimatedVisitsArray = array('2015-08-01' => '211972292');

  } else {

    global $clientFacade, $period, $start, $end, $main;

    $repsonse  = $clientFacade->getTrafficProResponse($domain, $period, $start, $end, $main);
    $estimatedVisitsArray = $repsonse->getValues();

  }

  foreach($estimatedVisitsArray AS $date => $visits){

      $estimatedVisits = number_format($visits);

  }

  return $estimatedVisits;

}

function getTrafficData($domain){
  // Global & Country Rank & % Global Traffic & % Traffic Sources

  global $debug;

  if($debug) {

    $trafficData['globalRank'] = '106,975';
    $trafficData['countryRank'] = '5,848';
    $globalTrafficArray = array('826' => '0.9444339', '036' => '0.0444339', '528' => '0.0244339');
    $trafficSourcesArray = array('Search' => '0.631124', 'Social' => '0.0212211');

  } else {

    global $clientFacade;

    $response = $clientFacade->getTrafficResponse($domain);
    $trafficData['globalRank'] = number_format($response->getGlobalRank());
    $trafficData['countryRank'] = number_format($response->getCountryRank());
    $globalTrafficArray = $response->getTopCountryShares();
    $trafficSourcesArray = $response->getTrafficShares();

  }

  foreach($globalTrafficArray AS $country => $share){

    if($share > 0.01) {

      $country = sprintf('%03u', $country);
      $iso3166 = new Alcohol\ISO3166();
      $iso3166->getByNumeric($country);

      $trafficData['globalTraffic'][$iso3166->getByNumeric($country)['name']] =  round((float)$share * 100) . '%';

    }
  }

  foreach($trafficSourcesArray AS $source => $share){

      $trafficData['trafficSources'][$source] =  round((float)$share * 100) . '%';

  }

  return $trafficData;

}

function getPageViews($domain){

  global $debug;

  if($debug){

    $pageViewsArray = array('2015-08-01' => '4.7798156608071');

  } else {

    global $clientFacade, $period, $start, $end, $main;

    $repsonse  = $clientFacade->getEngagementPageViewsResponse($domain, $period, $start, $end, $main);
    $pageViewsArray = $repsonse->getValues();

  }

  foreach($pageViewsArray AS $date => $views){

      $pageViews = number_format($views, 2);

  }

  return $pageViews;

}

function getVisitDuration($domain) {

  global $debug;

  if($debug){

    $visitDurationArray = array('2015-08-01' => '341.45500258871');

  } else {

    global $clientFacade, $period, $start, $end, $main;

    $repsonse  = $clientFacade->getEngagementVisitDurationResponse($domain, $period, $start, $end, $main);
    $visitDurationArray = $repsonse->getValues();

  }

  foreach($visitDurationArray AS $date => $duration){

      $visitDuration = gmdate("i:s", $duration);

  }

  return $visitDuration;

}

function getBounceRate($domain){

  global $debug;

  if($debug){

    $bounceRateArray = array('2015-08-01' => '0.36216955');

  } else {

    global $clientFacade, $period, $start, $end, $main;

    $repsonse  = $clientFacade->getEngagementBounceRateResponse($domain, $period, $start, $end, $main);
    $bounceRateArray = $repsonse->getValues();

  }

  foreach($bounceRateArray AS $date => $rate){

    $bounceRate = round((float)$rate * 100) . '%';

  }

  return $bounceRate;

}

function getSocialReferrals($domain){

  global $debug;

  if($debug) {

    $socialReferralsArray = array('Facebook' => '0.89836854863767', 'Twitter' => '0.045400909560531', 'Youtube' => '0.030839348316105');

  } else {

    global $clientFacade;

    $response = $clientFacade->getSocialReferralsResponse($domain);
    $socialReferralsArray = $response->getSocialSources();

  }

  foreach($socialReferralsArray AS $source => $share){

    if($share > 0.01) {

      $socialReferrals[$source] =  round((float)$share * 100) . '%';

    }

  }

  return $socialReferrals;

}

function calculateScores($data) {

  foreach($data AS $site => $stats) {

    $estimatedVisits[$site] = str_replace(',','',$stats['estimatedVisits']);
    $globalRank[$site] = str_replace(',','',$stats['trafficData']['globalRank']);
    $countryRank[$site] = str_replace(',','',$stats['trafficData']['countryRank']);
    $pageViews[$site] = $stats['pageViews'];
    $visitDuration[$site] = $stats['visitDuration'];
    $bounceRate[$site] = $stats['bounceRate'];

  }

  global $topscore;

  arsort($estimatedVisits, SORT_NUMERIC);
  $i=1;
  $s=$topscore;
  foreach($estimatedVisits AS $site => $stats){

    if($data[$site]['estimatedVisits'] > 0){

      $data[$site]['estimatedVisits'] .= ' (#'.$i.')';
      $i++;
      $data[$site]['score']['estimatedVisits'] = $s;
      $s=$s-0.1;
    } else {

       $data[$site]['estimatedVisits'] = 'N/A';

    }

  }

  asort($globalRank, SORT_NUMERIC);
  $i=1;
  $s=$topscore;
  foreach($globalRank AS $site => $stats){

    if($data[$site]['trafficData']['globalRank'] > 0){

      $data[$site]['trafficData']['globalRank'] .= ' (#'.$i.')';
      $i++;
      $data[$site]['score']['globalRank'] = $s;
      $s=$s-0.1;

    } else {

       $data[$site]['trafficData']['globalRank'] = 'N/A';

    }

  }

  asort($countryRank, SORT_NUMERIC);
  $i=1;
  $s=$topscore;
  foreach($countryRank AS $site => $stats){

    if($data[$site]['trafficData']['countryRank'] > 0){

      $data[$site]['trafficData']['countryRank'] .= ' (#'.$i.')';
      $i++;
      $data[$site]['score']['countryRank'] = $s;
      $s=$s-0.1;

    } else {

      $data[$site]['trafficData']['countryRank'] = 'N/A';

    }

  }

  arsort($pageViews, SORT_NUMERIC);
  $i=1;
  $s=$topscore;
  foreach($pageViews AS $site => $stats){

    if($data[$site]['pageViews'] > 0){

      $data[$site]['pageViews'] .= ' (#'.$i.')';
      $i++;
      $data[$site]['score']['pageViews'] = $s;
      $s=$s-0.1;

    } else {

      $data[$site]['pageViews'] = 'N/A';

    }

  }

  arsort($visitDuration, SORT_NUMERIC);
  $i=1;
  $s=$topscore;
  foreach($visitDuration AS $site => $stats){

    if($data[$site]['visitDuration'] > 0){

      $data[$site]['visitDuration'] .= ' (#'.$i.')';
      $i++;
      $data[$site]['score']['visitDuration'] = $s;
      $s=$s-0.1;

    } else {

      $data[$site]['visitDuration'] = 'N/A';

    }

  }

  asort($bounceRate, SORT_NUMERIC);
  $i=1;
  $s=$topscore;
  foreach($bounceRate AS $site => $stats){

    if($data[$site]['bounceRate'] > 0){

      $data[$site]['bounceRate'] .= ' (#'.$i.')';
      $i++;
      $data[$site]['score']['bounceRate'] = $s;
      $s=$s-0.1;

    } else {

      $data[$site]['bounceRate'] = 'N/A';

    }

  }

  // handle empty array

  foreach($data AS $site =>$stats){

    if(is_array($stats['score'])) {

      $data[$site]['score']['average'] = round((float)array_sum($stats['score'])/sizeof($stats['score']) * 100) . '%';

    } else {

      $data[$site]['score']['average'] = '0%';

    }

  }



  return $data;

}

// get the similar sites for the table columns
function getSimilarSites($domain){

  /*
  --- Similar Websites API--- 3 credits
  - Similar websites
  */

  global $debug;

  if($debug) {

    $similarWebsitesArray = array ('independenthostelguide.co.uk' => '0.99999057884519', 'syha.org.uk' => '0.99338837015131', 'ukhostels.com' => '0.98922415720146', 'hostelworld.com' => '0.90116783602465', 'youth-hostels.co.uk' => '0.87212708570852');

  } else {

    global $clientFacade;

    $response = $clientFacade->getSimilarWebsitesResponse($domain);
    $similarWebsitesArray = $response->getSimilarWebsites();

  }

  $similarWebsites = array_slice($similarWebsitesArray, 0, 5, true);

  return $similarWebsites;

}

?>

<!DOCTYPE html>
<html>

  <head>

    <meta charset="UTF-8">

    <title>CX Lite Prototype - <?php print $domain ?></title>

    <style>

      h2 {border-top:1px dotted black; margin-top:20px; padding-top:20px;}
      tr, td, th {border: 1px dotted black; padding:5px;}
      th:first-child {border: none;}
      .label {font-weight: bold;}
      form {width:350px; overflow:hidden;}
      label {padding:0 5px; width:157px;float: left}
      input {padding:5px; margin-bottom:10px;float: left}
      .row {float: left}
      .top {margin-bottom:20px;}
      .smallInput {width:40px;}
      .percentage {line-height:25px;padding-left:10px;}
      .submit {float:right;}

    </style>

  </head>

  <body>

    <h1>CX Lite</h1>

    <form id="selectDomain" action="" method="post">

      <div class="row top">
        <label for="top">Top</label>
        <input class="smallInput" name="top" type="text" value="65"/>
        <span class="percentage">%</span>
      </div>
      <div class="row">
        <label for="domain">Enter Domain</label>
        <input name="domain" type="text" required placeholder="eg: yha.org.uk"/>
      </div>
      <div class="row">
        <label for="domain1">Alternative Domain 1</label>
        <input name="domain1" type="text" placeholder="eg: yha.org.uk"/>
      </div>
      <div class="row">
        <label for="domain2">Alternative Domain 2</label>
        <input name="domain2" type="text" placeholder="eg: yha.org.uk"/>
      </div>
      <div class="row">
        <label for="domain3">Alternative Domain 3</label>
        <input name="domain3" type="text" placeholder="eg: yha.org.uk"/>
      </div>
      <div class="row">
        <label for="domain4">Alternative Domain 4</label>
        <input name="domain4" type="text" placeholder="eg: yha.org.uk"/>
      </div>
      <div class="row">
        <label for="domain5">Alternative Domain 5</label>
        <input name="domain5" type="text" placeholder="eg: yha.org.uk"/>
      </div>
      <div class="row submit">
        <input type="submit" />
      </div>
    </form>

    <?php if($domain) { ?>

      <h2>Results for <?php print $domain ?></h2>

      <table>
        <tr>
          <th></th>
          <?php foreach($data AS $domain => $stats){
            print '<th>'. $domain .'</th>';
          } ?>
        </tr>
        <tr>
          <td class="label">Estimated Number of Visits <br /> (<?php print $month ?>)</td>
          <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['estimatedVisits'] .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">Global Rank</td>
          <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['trafficData']['globalRank'] .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">Country Rank</td>
          <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['trafficData']['countryRank'] .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">% Global Traffic (above 1%)</td>
          <?php foreach($data AS $domain => $stats){
            $traffic = '';
            if(is_array($stats['trafficData']['globalTraffic'])) {
              foreach($stats['trafficData']['globalTraffic'] AS $country => $share){
                $traffic .= $country . ' - ' . $share . '<br />';
              }
            } else {
              $traffic = 'N/A';
            }
            print '<td>'. $traffic .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">% Traffic Sources</td>
          <?php foreach($data AS $domain => $stats){
            $traffic = '';
            foreach($stats['trafficData']['trafficSources'] AS $source => $share){
              $traffic .= $source . ' - ' . $share . '<br />';
            }
            print '<td>'. $traffic .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">Average Page Views per Session<br /> (<?php print $month ?>)</td>
          <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['pageViews'] .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">Average Visit Duration<br /> (<?php print $month ?>)</td>
          <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['visitDuration'] .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">Bounce Rate<br /> (<?php print $month ?>)</td>
          <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['bounceRate'] .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">% Social Referrals (above 1%)</td>
          <?php foreach($data AS $domain => $stats){
            $traffic = '';
            if(is_array($stats['socialReferrals'])) {
              foreach($stats['socialReferrals'] AS $source => $share){
                $traffic .= $source . ' - ' . $share . '<br />';
              }
            } else {
              $traffic .= 'N/A';
            }
            print '<td>'. $traffic .'</td>';
          } ?>
        </tr>
        <tr>
          <td class="label">Score</td>
            <?php foreach($data AS $domain => $stats){
            print '<td>'. $stats['score']['average'] .'</td>';
            } ?>
        </tr>
      </table>

      <p>
        <?php if($debug){
          print 'debug mode no credits used';
        } else {
          print $hits .' hits used (costing $'. $cost .')';
        }?>
      </p>
      <a  href="/download.php"><button>Download CSV</button></a>

    <?php } ?>

  </body>

</html>
