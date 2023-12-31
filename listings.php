<?php

session_start();

if (isset($_SESSION["user_id"])) {
    
    $servername = "localhost";
    $username = "hplante";
    $password = "pwpwpwpw";
    $database = "hplante";

    $connection = new mysqli($servername, $username, $password, $database);
    if ($connection->connect_error) {
      die("Connection failed: " . $connection->connect_error);
    }
    $sql = "SELECT * FROM Users WHERE user_id = {$_SESSION["user_id"]}";
          
    $result = $connection->query($sql);
    
    $user = $result->fetch_assoc();
    $balance = $user['balance'];
    $balance_str = "Current Balance: $" . $balance;
}
else {
    Header("Location: index.php");
  }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
        <link rel="stylesheet" href="listings.css">
        <title>Bets for Sale</title>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg bg-light fixed-top">
            <div class="container-fluid">
              <a class="navbar-brand" href="index.php">BetsButStocks</a>
              <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse align-center" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                  <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="nfl.php">Bet NFL</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="listings.php">Bets For Sale</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="my_bets.php">My Bets</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="my_account.php">My Account</a>
                  </li>
                  <?php
                   if ($_SESSION["user_id"] == 77 or $_SESSION["user_id"] == 134) {
                    echo "<li class='nav-item'>
                            <a class='nav-link active' aria-current='page' href='dashboard.php'>Dashboard</a>
                          </li>";
                   }
                  ?>
                </ul>
                <h5><?= htmlspecialchars($balance_str) ?></h5>
              </div>
            </div>
          </nav>
    <div class="bg-image">
    <div class="unique_container">
        <h1>Available Bets</h1>
        <h5>Spreads</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>Team</th>
                    <!-- <th>Odds</th> -->
                    <th>Line</th>
                    <th>Payout </th>
                    <th>List Price</th>
                    <th>Available Until</th>
                    <th>Listed Odds</th>
            </thead>
            <tbody>
              <?php
                    // read from database table
                    $sql = "(select away_team as team, s_away_odds as odds, s_away_line as line, sale_price, for_sale, amount, contract_id, game_date  
                            from Contracts, Games 
                            where type = 'Spread' 
                                and Contracts.game_id = Games.game_id 
                                and Contracts.bet_choice = 'Away' 
                                and for_sale = 1 
                                and paidout = 0 
                                and user_id != " . $_SESSION["user_id"] . " 
                                and Games.game_date > date_add(now(), interval -2 hour)) 
                            union (select home_team as team, s_home_odds as odds, s_home_line as line, sale_price, for_sale, amount, contract_id, game_date  
                                from Contracts, Games 
                                where type = 'Spread' 
                                    and Contracts.game_id = Games.game_id 
                                    and Contracts.bet_choice = 'Home' 
                                    and for_sale = 1 and paidout = 0 
                                    and user_id != " . $_SESSION["user_id"] . " 
                                    and Games.game_date > date_add(now(), interval -2 hour));";
                    $result = $connection->query($sql);

                    //read data from each row
                    while($row = $result->fetch_assoc()) {
                        $payout = 0;
                        if ($row['odds'] < 0) {
                            $payout = round((100 / (-1 *$row['odds'])) * $row['amount']) + $row['amount'];
                            $win = $payout - $row['amount'];
                            $list_odds = round((-100 * $row['sale_price']) / $win);
                        } else {
                            $payout = round(($row['odds'] / 100) * $row['amount']) + $row['amount'];
                            $win = $payout - $row['amount'];
                            $list_odds = "+" . round((100 * $win) / $row['sale_price']);
                        }
                        //$payout = $payout + $row['amount'];
                        //if (intval($row['odds']) > 0) { $row['odds'] = "+". $row['odds']; }
                        //if (intval($row['line']) > 0) { $row['line'] = "+". $row['line']; }

                        $dt = $row['game_date'];

                        $date = explode(' ', $dt)[0];
                        $date_comp = explode('-', $date);
                        $month = $date_comp[1];
                        $day = $date_comp[2];

                        $time = explode(' ', $dt)[1];
                        $time_comp = explode(':', $time);
                        $hour = intval($time_comp[0]) + 2;
                        $min = $time_comp[1];

                        if ($hour >= 12) {
                            $AMorPM = 'PM';
                            if ($hour > 12) {
                                $hour = $hour - 12;
                            }
                        }
                        else {
                            $AMorPM = 'AM';
                            if ($hour == 0) {
                                $hour = $hour + 12;
                            }
                        }

                        $available_until = $month . '/' . $day . ' at ' . $hour . ':' . $min . ' ' . $AMorPM;

                        echo "<tr>
                            <td>
                                <ul class=\"list-group\" style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id']." >". "<b>" . $row['team'] . "</b>" ."</li>
                                
                            </td>
                            <!-- <td>
                                <ul class=\"list-group\"  style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id'].">"." (" . $row['odds'] . ")" . "</li> 
                                </ul>
                            </td> -->
                            <td>
                                <ul class=\"list-group\" style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id'].">". $row['line'] . "</li>
                                </ul>
                            </td>
                            <td>
                                <ul class=\"list-group\" style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">". "$" . $payout . "</li>
                                </ul>
                            </td>
                            <td>
                                <ul class=\"list-group\" style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . "$" . $row['sale_price'] . "</li>
                                </ul>
                            </td>

                            <td>
                                <ul class=\"list-group\" style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . $available_until . "</li>
                                </ul>
                            </td>
                            <td>
                                <ul class=\"list-group\" style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . $list_odds . "</li>
                                </ul>
                            </td>

                            <td>
                                <ul class=\"list-group\">
                                <button class=\"btn\" id=".$row['contract_id']." onclick=\"showBet(this)\"><li class=\"list-group-item\"><b>Purchase</b></li></button>
                                </ul>
                            </td>
                        </tr>";
                    }

                ?>
            </tbody>
        </table>
        <h5>Moneylines</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>Team</th>
                    <!-- <th>Odds</th> -->
                    <th>Payout </th>
                    <th>List Price</th>
                    <th>Available Until</th>
                    <th>List Odds</th>
            </thead>
            <tbody>
              <?php
                    // read from database table
                    $sql = "(select away_team as team, ml_away_odds as odds, sale_price, for_sale, amount, contract_id, game_date  
                            from Contracts, Games 
                            where type = 'ML' 
                                and Contracts.game_id = Games.game_id 
                                and Contracts.bet_choice = 'Away' 
                                and for_sale = 1 
                                and paidout = 0 
                                and user_id != " . $_SESSION["user_id"] . " 
                                and Games.game_date > date_add(now(), interval -2 hour)) 
                            union (select home_team as team, ml_home_odds as odds, sale_price, for_sale, amount, contract_id, game_date  
                                from Contracts, Games 
                                where type = 'ML' 
                                    and Contracts.game_id = Games.game_id 
                                    and Contracts.bet_choice = 'Home' 
                                    and for_sale = 1 
                                    and paidout = 0 
                                    and user_id != " . $_SESSION["user_id"] . " 
                                    and Games.game_date > date_add(now(), interval -2 hour))";
                    $result = $connection->query($sql);

                    //read data from each row
                    while($row = $result->fetch_assoc()) {
                        $payout = 0;
                        $odds = $row['odds'];
                        $amt = $row['amount'];
                        $lst = $row['sale_price'];
                        echo "<h1>$odds odds for $amt listed for $lst</h1>";
                        if ($row['odds'] < 0) {
                            $payout = round((100 / (-1 *$row['odds'])) * $row['amount']) + $row['amount'];
                            $win = $payout - $row['amount'];
                            $list_odds = round((-100 * $row['sale_price']) / $win);
                        } else {
                            $payout = round(($row['odds'] / 100) * $row['amount']) + $row['amount'];
                            $win = $payout - $row['amount'];
                            $list_odds = "+" . round((100 * $win) / $row['sale_price']);
                        }

                        //$payout = $payout + $row['amount'];
                        //if (intval($row['odds']) > 0) { $row['odds'] = "+". $row['odds']; }

                        $dt = $row['game_date'];

                        $date = explode(' ', $dt)[0];
                        $date_comp = explode('-', $date);
                        $month = $date_comp[1];
                        $day = $date_comp[2];

                        $time = explode(' ', $dt)[1];
                        $time_comp = explode(':', $time);
                        $hour = intval($time_comp[0]) + 2;
                        $min = $time_comp[1];

                        if ($hour >= 12) {
                            $AMorPM = 'PM';
                            if ($hour > 12) {
                                $hour = $hour - 12;
                            }
                        }
                        else {
                            $AMorPM = 'AM';
                            if ($hour == 0) {
                                $hour = $hour + 12;
                            }
                        }

                        $available_until = $month . '/' . $day . ' at ' . $hour . ':' . $min . ' ' . $AMorPM;

                        echo "<tr>
                                <td>
                                    <ul class=\"list-group\" style=\"margin: auto;\">
                                    <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id']." >". "<b>" . $row['team'] . "</b>" ."</li>
                                </td>
                                <!-- <td>
                                    <ul class=\"list-group\"  style=\"margin: auto;\">
                                    <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id'].">"." (" . $row['odds'] . ")" . "</li>
                                    </ul>
                                </td> -->
                                <td>
                                    <ul class=\"list-group\" style=\"margin: auto;\">
                                    <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">". "$" . $payout . "</li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class=\"list-group\" style=\"margin: auto;\">
                                    <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . "$" . $row['sale_price'] . "</li>
                                    </ul>
                                </td>

                                <td>
                                    <ul class=\"list-group\" style=\"margin: auto;\">
                                    <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . $available_until . "</li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class=\"list-group\" style=\"margin: auto;\">
                                    <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . $list_odds . "</li>
                                    </ul>
                                </td>

                                <td>
                                    <ul class=\"list-group\">
                                    <button class=\"btn\" id=".$row['contract_id']." onclick=\"showBet(this)\"><li class=\"list-group-item\"><b>Purchase</b></li></button>
                                    </ul>
                                </td>
                            </tr>";
                        }
                ?>
            </tbody>
        </table>
        <h5>OverUnders</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Side</th>
                    <!-- <th>Odds</th> -->
                    <th>Point Total</th>
                    <th>Payout </th>
                    <th>List Price</th>
                    <th>Available Until</th>
                    <th>List Odds</th>
            </thead>
            <tbody>
              <?php
                    // read from database table
                    $sql = "(select away_team, home_team, ou_under_odds as odds, ou_total as total, sale_price, for_sale, amount, contract_id, bet_choice, game_date 
                            from Contracts, Games 
                            where type = 'OU' 
                                and Contracts.game_id = Games.game_id 
                                and Contracts.bet_choice = 'Under' 
                                and for_sale = 1 
                                and paidout = 0 
                                and user_id != " . $_SESSION["user_id"] . " 
                                and Games.game_date > date_add(now(), interval -2 hour)) 
                            union (select away_team, home_team, ou_over_odds as odds, ou_total as total, sale_price, for_sale, amount, contract_id, bet_choice, game_date 
                                from Contracts, Games 
                                where type = 'OU' 
                                    and Contracts.game_id = Games.game_id 
                                    and Contracts.bet_choice = 'Over' 
                                    and for_sale = 1 
                                    and paidout = 0 
                                    and user_id != " . $_SESSION["user_id"] . " 
                                    and Games.game_date > date_add(now(), interval -2 hour))";
                    $result = $connection->query($sql);

                    $team_id = "". str_replace(" ", "-", $row['home_team']) . "%" . str_replace(" ", "-", $row['away_team']);
                    //read data from each row
                    while($row = $result->fetch_assoc()) {
                        $team_id = "". str_replace(" ", "-", $row['home_team']) . "%" . str_replace(" ", "-", $row['away_team']);
                        $payout = 0;
                        if ($row['odds'] < 0) {
                            $payout = round((100 / (-1 *$row['odds'])) * $row['amount']) + $row['amount'];
                            $win = $payout - $row['amount'];
                            $list_odds = round((-100 * $row['sale_price']) / $win);
                        } else {
                            $payout = round(($row['odds'] / 100) * $row['amount']) + $row['amount'];
                            $win = $payout - $row['amount'];
                            $list_odds = "+" . round((100 * $win) / $row['sale_price']);
                        }
                        //$payout = $payout + $row['amount'];
                        //if (intval($row['odds']) > 0) { $row['odds'] = "+". $row['odds']; }

                        $dt = $row['game_date'];

                        $date = explode(' ', $dt)[0];
                        $date_comp = explode('-', $date);
                        $month = $date_comp[1];
                        $day = $date_comp[2];

                        $time = explode(' ', $dt)[1];
                        $time_comp = explode(':', $time);
                        $hour = intval($time_comp[0]) + 2;
                        $min = $time_comp[1];

                        if ($hour >= 12) {
                            $AMorPM = 'PM';
                            if ($hour > 12) {
                                $hour = $hour - 12;
                            }
                        }
                        else {
                            $AMorPM = 'AM';
                            if ($hour == 0) {
                                $hour = $hour + 12;
                            }
                        }

                        $available_until = $month . '/' . $day . ' at ' . $hour . ':' . $min . ' ' . $AMorPM;

                        echo "<tr>
                        <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id']." >". "<b>" . $row['home_team'] . " vs " . $row['away_team'] . "</b>" ."</li>
                        </td>
                        <td>
                            <ul class=\"list-group\"  style=\"margin: auto;\">
                                <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\" id=".$row['contract_id'].">" . $row['bet_choice']  . "</li>
                            </ul>
                        </td>
                        <!-- <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">". $row['odds'] . "</li> 
                            </ul>
                        </td>-->
                        <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">". $row['total'] . "</li>
                            </ul>
                        </td>
                        <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">". "$" . $payout . "</li>
                            </ul>
                        </td>
                        <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . "$" . $row['sale_price'] . "</li>
                            </ul>
                        </td>

                        <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . $available_until . "</li>
                            </ul>
                        </td>
                        <td>
                            <ul class=\"list-group\" style=\"margin: auto;\">
                            <li class=\"list-group-item\" style=\"margin-top: 7px; text-align: center\"id=".$row['contract_id'].">" . $list_odds . "</li>
                            </ul>
                        </td>

                        <td>
                            <ul class=\"list-group\">
                            <button class=\"btn\" id=".$row['contract_id']." onclick=\"showBet(this)\"><li class=\"list-group-item\"><b>Purchase</b></li></button>
                            </ul>
                        </td>
                    </tr>";
                    }

                ?>
            </tbody>
        </table>
      </div>
    </div>
      <div class="fixed-bottom card bg-light" id="bet-preview" style="display: none">
      </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.min.js" integrity="sha384-IDwe1+LCz02ROU9k972gdyvl+AESN10+x7tBKgc9I5HFtuNz0wWnPclzo6p9vxnk" crossorigin="anonymous"></script>
        <script type="text/javascript" src="contract_transfer.js"></script>
      </body>
</html>