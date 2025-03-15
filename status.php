<?php
include 'db_connect.php';
session_start();
$uid = $_SESSION['uid'];
$oid = $_GET['oid'];

if (!isset($_SESSION['uid'])) {
    header('Location: ./error.php?no=3');
    exit(); // Stop further execution
}

// Fetch order details, customer's address, and restaurant coordinates from the database
$sqlo = "SELECT Address.state as state, Address.city as city, Address.street as street, Address.pincode as pin, 
                Address.latitude as cust_lat, Address.longitude as cust_lng,
                Menu.item_name as mname, Orders.order_id as oid, Orders.order_total as price, 
                Drivers.name as dname, Orders.delivery_status as status, Menu.img as img, Drivers.phone,
                Restaurants.latitude as res_lat, Restaurants.longitude as res_lng 
         FROM Orders 
         JOIN Address ON Orders.user_id = Address.user_id 
         JOIN Menu ON Orders.menu_id = Menu.menu_id 
         JOIN Drivers ON Orders.driver_id = Drivers.driver_id 
         JOIN Restaurants ON Menu.restaurant_id = Restaurants.restaurant_id 
         WHERE order_id = $oid";
$reso = mysqli_query($conn, $sqlo);

if (!$reso) {
    die("Query failed: " . mysqli_error($conn)); // Debugging: Print SQL error
}

$row = mysqli_fetch_assoc($reso);

if (!$row) {
    die("No order found with ID: $oid"); // Debugging: Print error if no results are found
}

$mname = $row['mname'];
$price = $row['price'];
$dname = $row['dname'];
$status = $row['status'];
$img = $row['img'];
$phone = $row['phone'];
$state = $row['state'];
$street = $row['street'];
$city = $row['city'];
$pin = $row['pin'];
$cust_lat = $row['cust_lat'];
$cust_lng = $row['cust_lng'];
$res_lat = $row['res_lat'];
$res_lng = $row['res_lng'];

if ($status == "cooking") {
    $st = 1;
} elseif ($status == "pending") {
    $st = 2;
} elseif ($status == "delivered") {
    $st = 3;
}

$sqll = "SELECT * FROM Payment WHERE order_id = $oid";
$ress = mysqli_query($conn, $sqll);
$rest = mysqli_fetch_assoc($ress);
$pid = $rest["payment_id"];
$pm = $rest["payment_method"];
$pst = $rest["status"];
$pr = $rest["amount"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yummie Wheels</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
</head>
<body>
    <!-- MOBILE NAV -->
    <div class="mb-nav">
        <div class="mb-move-item"></div>
        <div class="mb-nav-item">
            <a href="./index.php">
                <i class="bx bxs-home"></i>
            </a>
        </div>
        <div class="mb-nav-item active">
            <a href="./res.php">
                <i class='bx bxs-wink-smile'></i>
            </a>
        </div>
        <div class="mb-nav-item">
            <a href="./order.php">
                <i class='bx bxs-food-menu'></i>
            </a>
        </div>
        <div class="mb-nav-item">
            <a href="./login.php">
                <i class='bx bxs-comment-detail'></i>
            </a>
        </div>
    </div>
    <!-- END MOBILE NAV -->
    <!-- BACK TO TOP BTN -->
    <a href="#" class="back-to-top">
        <i class="bx bxs-to-top"></i>
    </a>
    <!-- END BACK TO TOP BTN -->
    <!-- TOP NAVIGATION -->
    <div class="nav">
        <div class="menu-wrap">
            <a href="./index.php">
                <div class="logo">
                    Yummie Wheels
                </div>
            </a>
            <div class="menu h-xs">
                <a href="./index.php">
                    <div class="menu-item">
                        Home
                    </div>
                </a>
                <a href="./res.php">
                    <div class="menu-item">
                        Restaurants
                    </div>
                </a>
                <a href="./order.php">
                    <div class="menu-item active">
                        Orders
                    </div>
                </a>
                <a href="./login.php">
                    <div class="menu-item">
                        <?php
                        if (!isset($_SESSION['uid'])) {
                            echo "Login";
                        } else {
                            echo "Profile";
                        }
                        ?>
                    </div>
                </a>
            </div>
            <div class="right-menu">
                <div class="cart-btn">
                    <i class='bx bx-cart-alt'></i>
                </div>
            </div>
        </div>
    </div>
    <section>
        <section class="root">
            <figure>
                <img src="<?php echo $img ?>" alt="" class="figure-img">
                <figcaption>
                    <h4>Tracking Details:</h4>
                    <h4><?php echo $mname ?></h4>
                    <h6>Order Number # <?php echo $oid ?></h6>
                    <h4>Delivery Agent: <?php echo $dname ?></h4>
                    <h6>Call:<a href="tel:+91<?php echo $phone ?>"> +91<?php echo $phone ?></a></h6>
                    <br>
                    <h4>Shipping Address:</h4><br>
                    <h6><?php echo $street . "<br>" . $city . "<br>" . $state . "<br>" . $pin; ?></h6>
                    <h4>Payment Details:</h4>
                    <h6><?php echo "Transaction ID: " . $pid . "<br>Payment Method: " . $pm . "<br>Status: " . $pst . "<br> Price: " . $pr; ?></h6>
                </figcaption>
            </figure>
            <!-- Leaflet Map -->
            <div id="map" style="height: 400px; width: 100%;"></div>
            <!-- Estimated Time -->
            <div id="time" style="text-align: center; margin-top: 20px; font-size: 18px; font-weight: bold;">
                Estimated Delivery Time: <span id="estimated-time">Calculating...</span>
            </div>
        </section>
    </section>

    <!-- FOOTER SECTION -->
    <section class="footer bg-img" style="background-color: var(--third-color);">
        <div class="container">
            <div class="row">
                <div class="col-6 col-xs-12">
                    <h1>
                        Yummie Wheels
                    </h1>
                    <br>
                    <p>Yummie Wheels is a food delivery service that brings delicious meals to your doorsteps in minutes. Whatever you are craving for pizza, pasta, biriyani.</p>
                    <br>
                    <p>Email: <a href="mailto:contact@yummie.com">contact@yummie.com</a></p>
                    <p>Phone: <a href="tel:+911234567890">+911234567890</a></p>
                    <p>Website: yummiefood.com</p>
                </div>
                <div class="col-2 col-xs-12">
                    <h1>
                        About us
                    </h1>
                    <br>
                    <p>
                        <a href="#">
                            Privacy policy
                        </a>
                    </p>
                    <p>
                        <a href="#">
                            Contact
                        </a>
                    </p>
                    <p>
                        <a href="#">
                            About
                        </a>
                    </p>
                </div>
                <div class="col-4 col-xs-12">
                    <h1>
                        Subscribe & media
                    </h1>
                    <br>
                    <p>Subscribe For official information and Discounts and more</p>
                    <div class="input-group" style="background-color: var(--secondary-color);">
                        <input required="text" placeholder="Enter your email">
                        <button onClick="window.location.href='./login.php';">
                            Subscribe
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine JS -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
        // Initialize the map with dynamic coordinates
        var cust_lat = <?php echo $cust_lat; ?>;
        var cust_lng = <?php echo $cust_lng; ?>;
        var res_lat = <?php echo $res_lat; ?>;
        var res_lng = <?php echo $res_lng; ?>;

        var map = L.map('map').setView([cust_lat, cust_lng], 13); // Set the initial view to the customer's location

        // Add a tile layer (you can use OpenStreetMap or any other tile provider)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add markers for restaurant and customer
        var restaurantMarker = L.marker([res_lat, res_lng]).addTo(map)
            .bindPopup('Restaurant Location')
            .openPopup();

        var customerMarker = L.marker([cust_lat, cust_lng]).addTo(map)
            .bindPopup('Customer Location')
            .openPopup();

        // Add routing control
        var control = L.Routing.control({
            waypoints: [
                L.latLng(res_lat, res_lng), // Restaurant location
                L.latLng(cust_lat, cust_lng) // Customer location
            ],
            routeWhileDragging: true,
            show: true
        }).addTo(map);

        // Listen for route calculation
        control.on('routesfound', function (e) {
            var routes = e.routes;
            var route = routes[0];
            var time = Math.round(route.summary.totalTime / 60); // Convert seconds to minutes
            document.getElementById('estimated-time').innerText = time + ' minutes';
        });
    </script>
</body>
</html>