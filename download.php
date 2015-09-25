<?php

header ( "Content-Type: application/vnd.ms-excel" );
header ( "Content-disposition: attachment; filename=Export.csv" );
header ( "Content-Type: application/force-download" );
header ( "Content-Transfer-Encoding: binary" );
header ( "Pragma: no-cache" );
header ( "Expires: 0" );

session_start();

foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= ",".$domain;
}

$csv .= "\r\nEstimated Number of Visits,";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['estimatedVisits'].'"'.',';
}

$csv .= "\r\nGlobal Rank,";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['trafficData']['globalRank'].'"'.',';
}

$csv .= "\r\nCountry Rank,";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['trafficData']['countryRank'].'"'.',';
}

$csv .= "\r\n% Global Traffic (above 1%),";
foreach($_SESSION['data'] AS $domain => $stats){
  $countryShares = '';
  foreach($stats['trafficData']['globalTraffic'] AS $country => $share){
      $countryShares .= $country. ' - '. $share . "\n";
  }
  $csv .= '"'.$countryShares.'"'.',';
}

$csv .= "\r\n% Traffic Sources,";
foreach($_SESSION['data'] AS $domain => $stats){
  $trafficSources = '';
  foreach($stats['trafficData']['trafficSources'] AS $source => $share){
      $trafficSources .= $source. ' - '. $share . "\n";
  }
  $csv .= '"'.$trafficSources.'"'.',';
}

$csv .= "\r\nAverage Page Views per Session (".$_SESSION['month']."),";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['pageViews'].'"'.',';
}

$csv .= "\r\nAverage Visit Duration (".$_SESSION['month']."),";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['visitDuration'].'"'.',';
}

$csv .= "\r\nBounce Rate (".$_SESSION['month']."),";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['bounceRate'].'"'.',';
}

$csv .= "\r\n% Social Referrals (above 1%),";
foreach($_SESSION['data'] AS $domain => $stats){
  $socialReferrals = '';
  foreach($stats['socialReferrals'] AS $source => $share){
      $socialReferrals .= $source. ' - '. $share . "\n";
  }
  $csv .= '"'.$socialReferrals.'"'.',';
}

$csv .= "\r\nScore,";
foreach($_SESSION['data'] AS $domain => $stats){
   $csv .= '"'.$stats['score']['average'].'"'.',';
}

echo ("$csv");
