<?php

session_start();

require_once "includes/config.php";


/* =========================================================
   CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   CHECK PROVIDER ROLE
   ========================================================= */

if (($_SESSION["role"] ?? "") !== "provider") {

    header("Location: dashboard.php");
    exit;
}


/* =========================================================
   PROVIDER ID
   ========================================================= */

$provider_id = (int) $_SESSION["user_id"];

$message = "";
$error = "";


/* =========================================================
   GET ALL SERVICES
   ========================================================= */

$services_sql = "
    SELECT id, service_name, category
    FROM services
    ORDER BY category ASC, service_name ASC
";

$services_result = $conn->query($services_sql);


/* =========================================================
   SAVE PROVIDER SERVICE
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $service_id = (int) ($_POST["service_id"] ?? 0);

    $price = trim($_POST["price"] ?? "");

    $price_type = $_POST["price_type"] ?? "negotiable";

    $description = trim($_POST["service_description"] ?? "");

    /*
       Provider anaweza kuandika LOCATION YOYOTE.
       Mfano:
       Dar es Salaam
       Arusha
       Mbezi Beach
       Kariakoo
       Mwanza
       Zanzibar
       etc.
    */
    $location = trim($_POST["location"] ?? "");

    $availability = $_POST["availability"] ?? "available";


    /* =====================================================
       VALIDATION
       ===================================================== */

    if ($service_id <= 0) {

        $error = "Tafadhali chagua huduma.";

    } elseif ($location === "") {

        $error = "Tafadhali weka location yako.";

    } elseif (strlen($location) > 100) {

        $error = "Location ni ndefu sana. Tafadhali iwe chini ya herufi 100.";

    } elseif (
        !in_array(
            $price_type,
            ["fixed", "hourly", "daily", "negotiable"],
            true
        )
    ) {

        $error = "Price type sio sahihi.";

    } elseif (
        !in_array(
            $availability,
            ["available", "busy", "unavailable"],
            true
        )
    ) {

        $error = "Availability sio sahihi.";

    } else {


        /* =================================================
           CHECK DUPLICATE SERVICE
           ================================================= */

        $check_sql = "
            SELECT id
            FROM provider_services
            WHERE provider_id = ?
            AND service_id = ?
        ";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $check_stmt->bind_param(
                "ii",
                $provider_id,
                $service_id
            );

            $check_stmt->execute();

            $check_result = $check_stmt->get_result();


            if ($check_result->num_rows > 0) {

                $error = "Huduma hii tayari ipo kwenye profile yako.";

            } else {


                /* =========================================
                   CHECK PRICE
                   ========================================= */

                $price_value = null;

                if ($price !== "") {

                    if (!is_numeric($price) || (float)$price < 0) {

                        $error = "Weka bei sahihi.";

                    } else {

                        $price_value = (float)$price;
                    }
                }


                /* =========================================
                   SAVE SERVICE
                   ========================================= */

                if ($error === "") {

                    $insert_sql = "
                        INSERT INTO provider_services
                        (
                            provider_id,
                            service_id,
                            price,
                            price_type,
                            service_description,
                            availability
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ";

                    $insert_stmt = $conn->prepare($insert_sql);


                    if (!$insert_stmt) {

                        $error = "Database error: " . $conn->error;

                    } else {

                        $insert_stmt->bind_param(
                            "iidsss",
                            $provider_id,
                            $service_id,
                            $price_value,
                            $price_type,
                            $description,
                            $availability
                        );


                        /* =================================
                           EXECUTE INSERT
                           ================================= */

                        if ($insert_stmt->execute()) {


                            /* =================================
                               CHECK PROVIDER PROFILE
                               ================================= */

                            $profile_check = $conn->prepare(
                                "
                                SELECT id
                                FROM provider_profiles
                                WHERE provider_id = ?
                                "
                            );


                            if (!$profile_check) {

                                $error = "Database error wakati wa profile.";

                            } else {

                                $profile_check->bind_param(
                                    "i",
                                    $provider_id
                                );

                                $profile_check->execute();

                                $profile_result =
                                    $profile_check->get_result();


                                /* =================================
                                   UPDATE EXISTING PROFILE
                                   ================================= */

                                if ($profile_result->num_rows > 0) {

                                    $update_profile = $conn->prepare(
                                        "
                                        UPDATE provider_profiles
                                        SET location = ?
                                        WHERE provider_id = ?
                                        "
                                    );


                                    if ($update_profile) {

                                        $update_profile->bind_param(
                                            "si",
                                            $location,
                                            $provider_id
                                        );

                                        $update_profile->execute();

                                        $update_profile->close();
                                    }


                                /* =================================
                                   CREATE NEW PROFILE
                                   ================================= */

                                } else {

                                    $create_profile = $conn->prepare(
                                        "
                                        INSERT INTO provider_profiles
                                        (
                                            provider_id,
                                            location
                                        )
                                        VALUES (?, ?)
                                        "
                                    );


                                    if ($create_profile) {

                                        $create_profile->bind_param(
                                            "is",
                                            $provider_id,
                                            $location
                                        );

                                        $create_profile->execute();

                                        $create_profile->close();
                                    }
                                }


                                $profile_check->close();
                            }


                            $insert_stmt->close();
                            $check_stmt->close();


                            /* =================================
                               RETURN TO PROVIDER DASHBOARD
                               ================================= */

                            header(
                                "Location: provider-dashboard.php?success=service_added"
                            );

                            exit;


                        } else {

                            $error =
                                "Imeshindikana kuhifadhi huduma. Jaribu tena.";

                            $insert_stmt->close();
                        }
                    }
                }
            }


            $check_stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Service | KaziHub Tanzania</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            padding: 20px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .top h1 {
            margin: 0;
            font-size: 28px;
        }

        .back {
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.07);
        }

        .intro {
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #2563eb;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 13px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .hint {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1d4ed8;
        }

        @media (max-width: 650px) {

            .container {
                margin: 20px auto;
            }

            .card {
                padding: 20px;
            }

            .row {
                grid-template-columns: 1fr;
            }

            .top {
                align-items: flex-start;
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="top">

        <h1>Add New Service 🛠️</h1>

        <a href="provider-dashboard.php" class="back">
            ← Back to Dashboard
        </a>

    </div>


    <div class="card">

        <p class="intro">
            Ongeza huduma unayotoa kwenye KaziHub Tanzania.
            Wateja wataweza kuiona na baadaye kuwasiliana nawe
            kwa ajili ya huduma.
        </p>


        <?php if ($error !== ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- SERVICE -->

            <div class="form-group">

                <label for="service_id">
                    Chagua Huduma
                </label>

                <select name="service_id"
                        id="service_id"
                        required>

                    <option value="">
                        -- Chagua huduma --
                    </option>

                    <?php

                    $current_category = "";

                    while ($service = $services_result->fetch_assoc()):

                        if ($current_category !== $service["category"]):

                            if ($current_category !== "") {
                                echo "</optgroup>";
                            }

                            $current_category = $service["category"];

                            echo '<optgroup label="' .
                                htmlspecialchars($current_category) .
                                '">';

                        endif;
                    ?>

                        <option value="<?php echo $service["id"]; ?>">

                            <?php
                            echo htmlspecialchars(
                                $service["service_name"]
                            );
                            ?>

                        </option>

                    <?php endwhile; ?>

                    <?php if ($current_category !== ""): ?>
                        </optgroup>
                    <?php endif; ?>

                </select>

                <div class="hint">
                    Chagua moja kati ya huduma zilizopo KaziHub.
                </div>

            </div>


            <!-- PRICE + TYPE -->

            <div class="row">

                <div class="form-group">

                    <label for="price">
                        Bei (TSh)
                    </label>

                    <input
                        type="number"
                        name="price"
                        id="price"
                        min="0"
                        step="0.01"
                        placeholder="Mfano: 50000"
                    >

                    <div class="hint">
                        Unaweza kuacha wazi kama bei ni negotiable.
                    </div>

                </div>


                <div class="form-group">

                    <label for="price_type">
                        Aina ya Bei
                    </label>

                    <select name="price_type"
                            id="price_type">

                        <option value="negotiable">
                            Negotiable
                        </option>

                        <option value="fixed">
                            Fixed Price
                        </option>

                        <option value="hourly">
                            Per Hour
                        </option>

                        <option value="daily">
                            Per Day
                        </option>

                    </select>

                </div>

            </div>


            <!-- LOCATION -->

            <select name="location" required>
    <option value="">-- Chagua Mkoa --</option>

    <option value="Arusha">Arusha</option>
    <option value="Dar es Salaam">Dar es Salaam</option>
    <option value="Dodoma">Dodoma</option>
    <option value="Geita">Geita</option>
    <option value="Iringa">Iringa</option>
    <option value="Kagera">Kagera</option>
    <option value="Katavi">Katavi</option>
    <option value="Kigoma">Kigoma</option>
    <option value="Kilimanjaro">Kilimanjaro</option>
    <option value="Lindi">Lindi</option>
    <option value="Manyara">Manyara</option>
    <option value="Mara">Mara</option>
    <option value="Mbeya">Mbeya</option>
    <option value="Morogoro">Morogoro</option>
    <option value="Mtwara">Mtwara</option>
    <option value="Mwanza">Mwanza</option>
    <option value="Njombe">Njombe</option>
    <option value="Pwani">Pwani</option>
    <option value="Rukwa">Rukwa</option>
    <option value="Ruvuma">Ruvuma</option>
    <option value="Shinyanga">Shinyanga</option>
    <option value="Simiyu">Simiyu</option>
    <option value="Singida">Singida</option>
    <option value="Songwe">Songwe</option>
    <option value="Tabora">Tabora</option>
    <option value="Tanga">Tanga</option>
    <option value="Zanzibar">Zanzibar</option>
</select>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label for="service_description">
                    Maelezo ya Huduma
                </label>

                <textarea
                    name="service_description"
                    id="service_description"
                    placeholder="Elezea kwa kifupi huduma unayotoa..."
                ></textarea>

            </div>


            <!-- AVAILABILITY -->

            <div class="form-group">

                <label for="availability">
                    Availability
                </label>

                <select name="availability"
                        id="availability">

                    <option value="available">
                        🟢 Available
                    </option>

                    <option value="busy">
                        🟡 Busy
                    </option>

                    <option value="unavailable">
                        🔴 Unavailable
                    </option>

                </select>

            </div>


            <button type="submit" class="submit-btn">
                Save Service
            </button>

        </form>

    </div>

</div>

</body>

</html>