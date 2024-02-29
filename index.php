<?php
session_start();
// Read the variables sent via POST from our API
$sessionId = $_POST['sessionId'];
$serviceCode = $_POST['serviceCode'];
$phoneNumber = $_POST['phoneNumber'];
$text = $_POST['text'];

// Database connection parameters
$servername = 'localhost';
$username = 'root'; // Change this to your database username
$password = ''; // Change this to your database password
$database = 'ussd'; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

function get_mpesa_access_token()
{
    // M-Pesa credentials
    $consumerKey = 'ZA9Qkfqguoq0iJtVkmAny1d9uE5P5mYRl2MnJT5SnA5E5ABo';
    $consumerSecret =
        '6mDMlj5vpbdj7OCekSmXFjJOWCzDPN4KsGa4KNPANlxXVLqTYaVmNzVGIsgJGGKS';
    $access_token_url =
        'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    // Create HTTP headers
    $headers = ['Content-Type:application/json; charset=utf8'];

    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    $result = curl_exec($curl);
    curl_close($curl);

    // Check for errors
    if ($result === false) {
        echo 'Error: Unable to retrieve access token';
        return false;
    }

    $result = json_decode($result);
    if (isset($result->access_token)) {
        return $result->access_token;
    } else {
        echo 'Access token not found in response';
        return false;
    }
}

// Obtain access token
$access_token = get_mpesa_access_token();
if (!$access_token) {
    echo 'Failed to obtain access token';
    exit(); // Exit if unable to obtain access token
}

// Function to initiate M-Pesa payment
function initiate_mpesa_payment($phone, $amount, $access_token)
{
    date_default_timezone_set('Africa/Nairobi');

    // M-Pesa credentials
    $BusinessShortCode = '4026345';
    $Passkey =
        '9d782d48aaaa5aef715853e5c099b2036cf76c7fbcbb8978bb986963116d98dc';
    $PartyA = $phone; // This is your phone number
    $AccountReference = 'latak';
    $TransactionDesc = 'Cart Payment';
    $Amount = $amount;
    $Timestamp = date('YmdHis'); //20181004151020
    $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

    // M-Pesa endpoint URLs
    $initiate_url =
        'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    // Headers for STK push
    $stkheader = [
        'Content-Type:application/json',
        'Authorization:Bearer ' . $access_token,
    ];

    // Request payload
    $curl_post_data = [
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $Amount,
        'PartyA' => $PartyA,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $PartyA,
        'CallBackURL' =>
            'https://7399-41-80-116-44.ngrok-free.app/ussd/callback.php', // Replace with actual callback URL
        'AccountReference' => $AccountReference,
        'TransactionDesc' => $TransactionDesc,
    ];

    // Initiate cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $initiate_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));

    // Execute cURL request
    $curl_response = curl_exec($curl);
    curl_close($curl);

    // Check for errors
    if ($curl_response === false) {
        echo 'Error: Unable to initiate M-Pesa payment';
        return false;
    }

    // Process response
    return $curl_response;
}

// Initialize variables to store user information
// $fullName = '';
$idNumber = '';
$location = '';
$phone = '';
$subscriptionOption = '1';
$subscriptionAmount = '0';

// Check the USSD input
if ($text == '') {
    // This is the first request. Note how we start the response with CON
    $response = "CON Are you a?: \n";
    $response .= "1. Landlord \n";
    $response .= '2. Tenant';
} elseif ($text == '1' || $text == '2') {
    // Prompt user to choose subscription
    $response = "CON Choose subscription:\n";
    $response .= "1. Jambo - Ksh100/month\n";
    $response .= "2. Jambo year - Ksh1000\n";
    $response .= "3. Bronze - Ksh2,400\n";
    $response .= "4. Silver - Ksh30,000\n";
    $response .= "5. Platinum - Ksh40,000\n";
    $response .= "6. Palladium - Ksh50,000\n";
    $response .= "7. Gold - Ksh100,000\n";
    $response .= "8. Rhodium - Ksh300,000\n";
} elseif (strpos($text, '*') !== false) {
    // Split text based on '*'
    $data = explode('*', $text);
    $inputData = explode('*', $text);
    // Get the step based on the count of data
    $subscriptionOption = isset($inputData[1]) ? $inputData[1] : '';
    $step = count($data);
    switch ($subscriptionOption) {
        case '1':
            $subscriptionAmount = 100;
            break;
        case '2':
            $subscriptionAmount = 1000;
            break;
        case '3':
            $subscriptionAmount = 2400;
            break;
        case '4':
            $subscriptionAmount = 30000;
            break;
        case '5':
            $subscriptionAmount = 40000;
            break;
        case '6':
            $subscriptionAmount = 50000;
            break;
        case '7':
            $subscriptionAmount = 100000;
            break;
        case '8':
            $subscriptionAmount = 300000;
            break;
        // default:
        //     $subscriptionAmount = 1;
        //     break;
    }
    switch ($step) {
        case 2:
            // User is choosing subscription

            $response = "CON Enter your full name: \n";
            break;
        case 3:
            // User is entering their full name
            // $fullName = $data[2];
            // $_SESSION[$data[2]] = $data[2];
            $response = "CON Enter your ID number: \n";
            break;
        case 4:
            // User is entering their ID number
            // $idNumber = $data[3];
            $response = "CON Enter your location: \n";
            break;
        case 5:
            // User is entering their location
            $location = isset($inputData[3]) ? $inputData[3] : '';
            $response = "CON Enter your phone number: \n";
            break;
        case 6:
            // User is entering their phone number
            $phone = $data[5];
            // Set subscription amount based on user choice
            $location = isset($inputData[4]) ? $inputData[4] : '';
            $idNumber = isset($inputData[3]) ? $inputData[3] : '';
            $fullName = isset($inputData[2]) ? $inputData[2] : '';

            // Insert user data into the database
            $sql = "INSERT INTO users (full_name, id_number, location, phone_number) VALUES ('$fullName', '$idNumber', '$location', '$phone')";

            if ($conn->query($sql) === true) {
                // Construct summary message with all captured information
                // $myname = $_SESSION[$data[2]];
                initiate_mpesa_payment(
                    $phone,
                    $subscriptionAmount,
                    $access_token
                );
                $summaryMessage = "Registration successful!\n";
                $summaryMessage .= "Full Name: $fullName\n";
                $summaryMessage .= "ID Number: $idNumber\n";
                $summaryMessage .= "Location: $location\n";
                $summaryMessage .= "Phone Number: $phone\n";
                $summaryMessage .= "Subscription Option: $subscriptionOption\n";
                $summaryMessage .= "Subscription Amount: Ksh $subscriptionAmount";

                // End the USSD session with the summary message
                $response = "END $summaryMessage";
            } else {
                $response = 'END Error: ' . $conn->error;
            }
            break;
        default:
            $response = 'END Invalid input.';
            break;
    }
}

// Close database connection
$conn->close();

// Echo the response back to the API
header('Content-type: text/plain');
echo $response;
?>
