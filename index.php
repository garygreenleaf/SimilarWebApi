<?php

require 'vendor/autoload.php';

use Thunder\SimilarWebApi\Client;
use Thunder\SimilarWebApi\ClientFacade;


// Config
$yourUserKey    = 'ca1476940ddbdb09b667b62dc5f3cbd4';
$desiredFormat  = 'json';
$domain         = filter_var($_POST['domain'], FILTER_SANITIZE_URL);

if($_GET['debug'] == 'off') {
  $debug        = false;
} else {
  $debug        = true;
}

$period         = 'monthly'; // daily, weekly, monthly *Default: monthly
$start          = date('m-Y', strtotime('first day of previous month')); // Start Month (MM-YYYY) *Required
$end            = date('m-Y', strtotime('first day of previous month')); // End Month (MM-YYYY) *Required
$main           = false; // false/true. Get metrics on the Main Domain only (i.e. not including subdomains). *Default: false

if($domain){

  // create client object
  $client = new Client($yourUserKey, $desiredFormat);
  $clientFacade = new ClientFacade($client);

  /******

  ** Stats to display **

  --- TrafficPro API --- 1 credit
  - Estimated number of visits for the domain

  --- Traffic API --- 1 credit
  - Global Rank
  - Country Rank
  - % Global Traffic
  - % Traffic Sources

  --- EngagementPageViews API --- 1 credit
  - Average Page Views

  --- EngagementVisitDuration API --- 1 credit
  - Average Visit Duration

  --- EngagementBounceRate API --- 1 credit
  - % Bounce Rate

  --- SocialReferrals API --- 1 credit
  - % of Traffic for each Social Network

  --- Similar Websites API--- 3 credits
  - Similar websites

  *******/

  // Estimated Visits

  if($debug){

    $estimatedVisitsArray = array('2015-08-01' => '211972292');
  } else {

    $repsonse  = $clientFacade->getTrafficProResponse($domain, $period, $start, $end, $main);
    $estimatedVisitsArray = $repsonse->getValues();

  }

  foreach($estimatedVisitsArray AS $date => $visits){

      $estimatedVisits = number_format($visits) . '<br />';
      $estimatedVisitsMonth = date('F Y' , strtotime($date));

  }


  // Global & Country Rank & % Global Traffic & % Traffic Sources

  if($debug) {

    $globalRank = '106,975';
    $countryRank = '5,848';
    $globalTrafficArray = array('826' => '0.9444339', '036' => '0.0444339', '528' => '0.0244339');
    $trafficSourcesArray = array('Search' => '0.631124', 'Social' => '0.0212211');

  } else {

    $response = $clientFacade->getTrafficResponse($domain);
    $globalRank = number_format($response->getGlobalRank());
    $countryRank = number_format($response->getCountryRank());
    $globalTrafficArray = $response->getTopCountryShares();
    $trafficSourcesArray = $response->getTrafficShares();

  }

  foreach($globalTrafficArray AS $country => $share){

    if($share > 0.01) {

      $country = sprintf('%03u', $country);
      $iso3166 = new Alcohol\ISO3166();
      $iso3166->getByNumeric($country);

      $globalTraffic .= $iso3166->getByNumeric($country)['name'] . ' - ' . round((float)$share * 100) . '%<br />';

    }
  }

  foreach($trafficSourcesArray AS $source => $share){

      $trafficSources .= $source . ' - ' . round((float)$share * 100) . '%<br />';

  }


  // Page Views

  if($debug){

    $pageViewsArray = array('2015-08-01' => '4.7798156608071');

  } else {

    $repsonse  = $clientFacade->getEngagementPageViewsResponse($domain, $period, $start, $end, $main);
    $pageViewsArray = $repsonse->getValues();

  }

  foreach($pageViewsArray AS $date => $views){

      $pageViews = number_format($views, 2) . '<br />';
      $pageViewsMonth = date('F Y' , strtotime($date));

  }


  // Visit Duration

  if($debug){

    $visitDurationArray = array('2015-08-01' => '341.45500258871');

  } else {

    $repsonse  = $clientFacade->getEngagementVisitDurationResponse($domain, $period, $start, $end, $main);
    $visitDurationArray = $repsonse->getValues();

  }

  foreach($visitDurationArray AS $date => $duration){

      $visitDuration = gmdate("i:s", $duration) . '<br />';
      $visitDurationMonth = date('F Y' , strtotime($date));

  }

    // Bounce Rate

  if($debug){

    $bounceRateArray = array('2015-08-01' => '0.36216955');

  } else {

    $repsonse  = $clientFacade->getEngagementBounceRateResponse($domain, $period, $start, $end, $main);
    $bounceRateArray = $repsonse->getValues();

  }

  foreach($bounceRateArray AS $date => $rate){

    $bounceRate = round((float)$rate * 100) . '%<br />';
    $bounceRateMonth = date('F Y' , strtotime($date));

  }

  // Social Referrals

  if($debug) {

    $socialReferralsArray = array('Facebook' => '0.89836854863767', 'Twitter' => '0.045400909560531', 'Youtube' => '0.030839348316105');

  } else {

    $response = $clientFacade->getSocialReferralsResponse($domain);
    $socialReferralsArray = $response->getSocialSources();

  }

  foreach($socialReferralsArray AS $source => $share){

    if($share > 0.01) {

      $socialReferrals .= $source . ' - ' . round((float)$share * 100) . '%<br />';

    }

  }

  // Similar Websites

  if($debug) {

    $similarWebsitesArray = array ('independenthostelguide.co.uk' => '0.99999057884519', 'syha.org.uk' => '0.99338837015131', 'ukhostels.com' => '0.98922415720146', 'hostelworld.com' => '0.90116783602465', 'youth-hostels.co.uk' => '0.87212708570852');

  } else {

    $response = $clientFacade->getSimilarWebsitesResponse($domain);
    $similarWebsitesArray = $response->getSimilarWebsites();

  }

  $i = 0;
  foreach($similarWebsitesArray AS $site => $match){

    if($i < 5) {

        $similarWebsites .= $site . '<br />';

    }

    $i++;

  }

}

?>

<!DOCTYPE html>
<html>

  <head>

    <meta charset="UTF-8">

    <title>CX Lite Prototype - <?php print $domain ?></title>

    <style>

      tr, td {border: 1px dotted black;}
      .label {font-weight: bold;}

    </style>

  </head>

  <body>

    <h1>CX Lite</h1>

    <form id="selectDomain" action="" method="post">

      <label for="domain">Enter Domain</label>
      <input name="domain" type="text" required placeholder="eg: yha.org.uk"/>
      <input type="submit" />

    </form>

    <?php if($domain) { ?>

      <h2>Results for <?php print $domain ?></h2>

      <table>
        <tr>
          <td class="label">Estimated Number of Visits <br /> (<?php print $estimatedVisitsMonth ?>)</td>
          <td><?php print $estimatedVisits ?></td>
        </tr>
        <tr>
          <td class="label">Global Rank</td>
          <td><?php print $globalRank ?></td>
        </tr>
        <tr>
          <td class="label">Country Rank</td>
          <td><?php print $countryRank ?></td>
        </tr>
        <tr>
          <td class="label">% Global Traffic (above 1%)</td>
          <td><?php print $globalTraffic ?></td>
        </tr>
        <tr>
          <td class="label">% Traffic Sources</td>
          <td><?php print $trafficSources ?></td>
        </tr>
        <tr>
          <td class="label">Average Page Views per Session<br /> (<?php print $pageViewsMonth ?>)</td>
          <td><?php print $pageViews ?></td>
        </tr>
        <tr>
          <td class="label">Average Visit Duration<br /> (<?php print $visitDurationMonth ?>)</td>
          <td><?php print $visitDuration ?></td>
        </tr>
        <tr>
          <td class="label">Bounce Rate<br /> (<?php print $bounceRateMonth ?>)</td>
          <td><?php print $bounceRate ?></td>
        </tr>
        <tr>
          <td class="label">% Social Referrals (above 1%)</td>
          <td><?php print $socialReferrals ?></td>
        </tr>
        <tr>
          <td class="label">5 Similar Websites</td>
          <td><?php print $similarWebsites ?></td>
        </tr>
      </table>

    <?php } ?>

  </body>

</html>
