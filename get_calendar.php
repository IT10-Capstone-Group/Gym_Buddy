<?php
session_start();
include 'php/config.php';

if (!isset($_GET['year']) || !isset($_GET['month']) || !isset($_GET['trainer_id'])) {
    echo "Missing parameters";
    exit();
}

$year = $_GET['year'];
$month = $_GET['month'];
$trainer_id = $_GET['trainer_id'];

// Fetch confirmed dates for this trainer
$sql = "SELECT date FROM bookings WHERE trainer_id = ? AND status = 'confirmed'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$confirmed_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Function to generate calendar
function generateCalendar($year, $month, $confirmed_dates) {
    $calendar = "";
    
    // Create array containing abbreviations of days of week
    $daysOfWeek = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

    // Get the first day of the month
    $firstDayOfMonth = mktime(0,0,0,$month,1,$year);

    // Get the number of days in the month
    $numberDays = date('t', $firstDayOfMonth);

    // Get info about the first day of the month
    $dateComponents = getdate($firstDayOfMonth);

    // Get the name of the month
    $monthName = $dateComponents['month'];

    // Get the index value 0-6 of the first day of the month
    $dayOfWeek = $dateComponents['wday'];

    // Create the table tag opener and day headers
    $calendar .= "<table class='calendar'>";
    $calendar .= "<caption>$monthName $year</caption>";
    $calendar .= "<tr>";

    // Create the calendar headers
    foreach($daysOfWeek as $day) {
        $calendar .= "<th class='header'>$day</th>";
    }

    $calendar .= "</tr><tr>";

    // Initiate the day counter
    $currentDay = 1;

    // The variable $dayOfWeek is used to ensure that the calendar
    // display consists of exactly 7 columns
    if ($dayOfWeek > 0) { 
        $calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>"; 
    }

    $month = str_pad($month, 2, "0", STR_PAD_LEFT);

    while ($currentDay <= $numberDays) {
        // Seventh column (Saturday) reached. Start a new row.
        if ($dayOfWeek == 7) {
            $dayOfWeek = 0;
            $calendar .= "</tr><tr>";
        }
        
        $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
        $date = "$year-$month-$currentDayRel";
        
        if (in_array($date, $confirmed_dates)) {
            $calendar .= "<td class='day confirmed' data-date='$date'>$currentDay</td>";
        } else {
            $calendar .= "<td class='day'>$currentDay</td>";
        }
        
        $currentDay++;
        $dayOfWeek++;
    }

    // Complete the row of the last week in month, if necessary
    if ($dayOfWeek != 7) { 
        $remainingDays = 7 - $dayOfWeek;
        $calendar .= "<td colspan='$remainingDays'>&nbsp;</td>"; 
    }

    $calendar .= "</tr>";
    $calendar .= "</table>";

    return $calendar;
}

echo generateCalendar($year, $month, $confirmed_dates);

