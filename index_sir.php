<?php
error_reporting(0);

$dir = '/var/www/html';


function get_domain_expiration_date($domain)
{

  $whois_server = 'whois.verisign-grs.com';

  $conn = fsockopen($whois_server, 43);
  if (!$conn) {
    return "Unable to connect to WHOIS server";
  }

  fputs($conn, "$domain\r\n");

  $response = '';
  while (!feof($conn)) {
    $response .= fgets($conn, 128);
  }
  fclose($conn);

  preg_match('/Expiry Date: (.+)/', $response, $matches);
  if (isset($matches[1])) {
    return trim($matches[1]);
  } else {
    return "Expiration date not found";
  }
}


function getSSLCertificateExpiration($domain, $port = 443)
{
  // Establish an SSL connection to the domain
  $ssl = stream_context_create(['ssl' => ['capture_peer_cert' => true]]);
  $stream = stream_socket_client("ssl://$domain:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ssl);

  if ($stream) {
    // Extract SSL certificate information
    $params = stream_context_get_params($stream);
    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

    // Calculate the expiration date
    if (isset($cert['validTo_time_t'])) {
      $expiration_date = $cert['validTo_time_t'];
      return $expiration_date;
    } else {
      return null; // Unable to retrieve expiration date
    }
  } else {
    return null; // Unable to establish SSL connection
  }
}



//$expiration_date = get_domain_expiration_date($domain);

//echo "The domain $domain expires on: $expiration_date";

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Website Static Page Tracker</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <style>
    @import url(https://fonts.googleapis.com/css?family=Open+Sans:600,700);

    .container,
    h1,
    h3 {
      text-align: center
    }

    * {
      font-family: 'Open Sans', sans-serif
    }

    .rwd-table {
      margin: auto;
      min-width: 300px;
      max-width: 100%;
      border-collapse: collapse;
      color: #333;
      border-radius: .4em;
      overflow: hidden
    }

    .rwd-table tr:first-child {
      border-top: none;
      background: #428bca;
      color: #fff
    }

    .rwd-table tr {
      border-top: 1px solid #ddd;
      border-bottom: 1px solid #ddd;
      background-color: #f5f9fc;
      border-color: #bfbfbf
    }

    .rwd-table tr:nth-child(odd):not(:first-child) {
      background-color: #ebf3f9
    }

    .rwd-table th {
      display: none
    }

    .container,
    .rwd-table td {
      display: block
    }

    .rwd-table td:first-child {
      margin-top: .5em
    }

    .rwd-table td:last-child {
      margin-bottom: .5em
    }

    .rwd-table td:before {
      content: attr(data-th) ": ";
      font-weight: 700;
      width: 120px;
      display: inline-block;
      color: #000
    }

    .rwd-table td,
    .rwd-table th {
      text-align: left;
      padding: .5em 1em
    }

    @media screen and (max-width:601px) {
      .rwd-table tr:nth-child(2) {
        border-top: none
      }
    }

    @media screen and (min-width:600px) {
      .rwd-table tr:hover:not(:first-child) {
        background-color: #d8e7f3
      }

      .rwd-table td:before {
        display: none
      }

      .rwd-table td,
      .rwd-table th {
        display: table-cell;
        padding: 1em !important
      }

      .rwd-table td:first-child,
      .rwd-table th:first-child {
        padding-left: 0
      }

      .rwd-table td:last-child,
      .rwd-table th:last-child {
        padding-right: 0
      }
    }

    body {
      background: #4b79a1;
      background: -webkit-linear-gradient(to left, #4b79a1, #283e51);
      background: linear-gradient(to left, #4b79a1, #283e51)
    }

    h1 {
      font-size: 2.4em;
      color: #f2f2f2
    }

    h3 {
      display: inline-block;
      position: relative;
      font-size: 1.5em;
      color: #cecece
    }

    h3:before {
      content: "\25C0";
      position: absolute;
      left: -50px;
      -webkit-animation: 2s linear infinite leftRight;
      animation: 2s linear infinite leftRight
    }

    h3:after {
      content: "\25b6";
      position: absolute;
      right: -50px;
      -webkit-animation: 2s linear infinite reverse leftRight;
      animation: 2s linear infinite reverse leftRight
    }

    @-webkit-keyframes leftRight {

      0%,
      100% {
        -webkit-transform: translateX(0)
      }

      25% {
        -webkit-transform: translateX(-10px)
      }

      75% {
        -webkit-transform: translateX(10px)
      }
    }

    @keyframes leftRight {

      0%,
      100% {
        transform: translateX(0)
      }

      25% {
        transform: translateX(-10px)
      }

      75% {
        transform: translateX(10px)
      }
    }

    .danger {
      background-color: #d94e4e !important;
    }

    .form-horizontal .form-group {
      margin-left: 5px;
    }
  </style>

</head>

<body>

  <div class="container">
    <h1>Website Domain & SSL Tracker</h1>
    <form class="form-horizontal" method="POST">

      <div class="col-md-6">
        <div class="form-group">
          <select class="form-control" name="ServerId" id="ServerId">
            <option value=''>--SELECT--</option>
            <option value='S1_SERVER'>S1_SERVER</option>
            <option value='S2_SERVER'>S2_SERVER</option>
            <option value='S3_SERVER'>S3_SERVER</option>

          </select>
        </div>
      </div>

      <div class="col-md-3">
        <div class="form-group">
          <button type="submit" name="submit" class="form-control btn btn-default">Submit</button>
        </div>
      </div>
    </form>
  </div>
  <hr>
  <div class="container">
    <table class="rwd-table" id="myTable">
      <tbody>
        <tr>
          <th>Website</th>
          <th onclick="sortTable(1)">Expiry Date</th>
          <th onclick="sortTable(2)">Remaining Days</th>
          <th onclick="sortTable(3)">SSL Expiration</th>
        </tr>


        <?php

        if (isset($_POST['ServerId']) && !empty($_POST['ServerId'])) {

          $file_path = $_POST['ServerId'] . '.txt';

          $file_handle = fopen($file_path, 'r');


          while (!feof($file_handle)) {

            $file = fgets($file_handle);
            $ExpiryDate = date("Y-m-d", strtotime(get_domain_expiration_date($file)));
            $CurrentDate = date('Y-m-d');
            $sslExpireDate = date('Y-m-d', getSSLCertificateExpiration($file));
            $diff = strtotime($ExpiryDate) - strtotime($CurrentDate);

            $Days = round($diff / 86400);

            if ($Days < 10) {
              $status = "danger";
              $renewalStatus = "Immediately Renew";
            } else {
              $status = '';
              $renewalStatus = '';
            }

            //$date = date("Y-m-d",strtotime(get_domain_expiration_date($file)));
        ?>
            <tr class="<?= $status; ?>">
              <td><?= $file; ?></td>
              <td><?= $ExpiryDate; ?></td>
              <td><?= $Days; ?> </td>
              <td><?= $sslExpireDate; ?> </td>
            </tr>

        <?php

          }
          fclose($file_handle);
        }
        ?>
  </div>
  <script>
    function sortTable(columnIndex) {
      var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
      table = document.getElementById("myTable");
      switching = true;
      // Set the sorting direction to ascending:
      dir = "asc";
      while (switching) {
        switching = false;
        rows = table.getElementsByTagName("tr");
        for (i = 1; i < (rows.length - 1); i++) {
          shouldSwitch = false;
          x = rows[i].getElementsByTagName("td")[columnIndex];
          y = rows[i + 1].getElementsByTagName("td")[columnIndex];
          if (dir == "asc") {
            if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
              shouldSwitch = true;
              break;
            }
          } else if (dir == "desc") {
            if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
              shouldSwitch = true;
              break;
            }
          }
        }
        if (shouldSwitch) {
          rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
          switching = true;
          switchcount++;
        } else {
          if (switchcount == 0 && dir == "asc") {
            dir = "desc";
            switching = true;
          }
        }
      }
    }
  </script>