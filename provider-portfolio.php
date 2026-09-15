<?php
session_start();

require_once "includes/config.php";

/* ==============================
   LOGIN CHECK
============================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "provider") {
    header("Location: dashboard.php");
    exit;
}

$provider_id = (int) $_SESSION["user_id"];


/* ==============================
   UPLOAD FOLDER
============================== */

$upload_folder = __DIR__ . "/uploads/provider_work/";
$upload_url = "uploads/provider_work/";


/* Create folders automatically */

if (!is_dir(__DIR__ . "/uploads")) {
    mkdir(__DIR__ . "/uploads", 0777, true);
}

if (!is_dir($upload_folder)) {
    mkdir($upload_folder, 0777, true);
}


/* ==============================
   MESSAGES
============================== */

$error = "";
$success = "";


/* ==============================
   DELETE WORK
============================== */

if (isset($_GET["delete"])) {

    $work_id = (int) $_GET["delete"];

    $stmt = $conn->prepare("
        SELECT image
        FROM provider_portfolio
        WHERE id = ? AND provider_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $work_id, $provider_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $work = $result->fetch_assoc();

    if ($work) {

        $file_path = $upload_folder . basename($work["image"]);

        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        $delete = $conn->prepare("
            DELETE FROM provider_portfolio
            WHERE id = ? AND provider_id = ?
        ");

        $delete->bind_param("ii", $work_id, $provider_id);
        $delete->execute();

        $delete->close();
    }

    $stmt->close();

    header("Location: provider-portfolio.php?deleted=1");
    exit;
}


/* ==============================
   UPLOAD WORK
============================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");

    /* Check title */

    if ($title === "") {

        $error = "Tafadhali weka jina la kazi.";

    }

    /* Check image */

    elseif (!isset($_FILES["work_image"])) {

        $error = "Tafadhali chagua picha.";

    }

    else {

        $file = $_FILES["work_image"];

        /* PHP upload error */

        if ($file["error"] !== UPLOAD_ERR_OK) {

            switch ($file["error"]) {

                case UPLOAD_ERR_INI_SIZE:
                    $error = "Picha ni kubwa kuliko kiwango kinachoruhusiwa na PHP.";
                    break;

                case UPLOAD_ERR_FORM_SIZE:
                    $error = "Picha ni kubwa sana.";
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $error = "Picha haikupakiwa kikamilifu. Jaribu tena.";
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $error = "Tafadhali chagua picha.";
                    break;

                default:
                    $error = "Upload imeshindwa. Error code: " . $file["error"];
            }

        }

        /* File size */

        elseif ($file["size"] > 5 * 1024 * 1024) {

            $error = "Picha haiwezi kuzidi 5MB.";

        }

        /* Check temporary file */

        elseif (!is_uploaded_file($file["tmp_name"])) {

            $error = "Faili haijapokelewa vizuri na server.";

        }

        else {

            /* Check image */

            $image_info = @getimagesize($file["tmp_name"]);

            if ($image_info === false) {

                $error = "Faili hili si picha halali.";

            }

            else {

                /* MIME type */

                $mime = $image_info["mime"];

                $allowed_types = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowed_types[$mime])) {

                    $error = "Tumia picha ya JPG, PNG au WEBP pekee.";

                }

                else {

                    $extension = $allowed_types[$mime];

                    /* Unique filename */

                    $filename =
                        "work_" .
                        $provider_id .
                        "_" .
                        date("YmdHis") .
                        "_" .
                        bin2hex(random_bytes(6)) .
                        "." .
                        $extension;

                    $destination = $upload_folder . $filename;


                    /* ==============================
                       MOVE IMAGE
                    ============================== */

                    if (!move_uploaded_file(
                        $file["tmp_name"],
                        $destination
                    )) {

                        $error =
                            "Picha imeshindwa kuhifadhiwa. " .
                            "Hakikisha folder uploads/provider_work lina permission ya kuandika.";

                    }

                    else {

                        /* ==============================
                           SAVE TO DATABASE
                        ============================== */

                        $stmt = $conn->prepare("
                            INSERT INTO provider_portfolio
                            (
                                provider_id,
                                image,
                                title,
                                description
                            )
                            VALUES (?, ?, ?, ?)
                        ");

                        if (!$stmt) {

                            @unlink($destination);

                            $error =
                                "Database error: " .
                                $conn->error;

                        }

                        else {

                            $stmt->bind_param(
                                "isss",
                                $provider_id,
                                $filename,
                                $title,
                                $description
                            );

                            if ($stmt->execute()) {

                                $stmt->close();

                                header(
                                    "Location: provider-portfolio.php?uploaded=1"
                                );

                                exit;

                            }

                            else {

                                @unlink($destination);

                                $error =
                                    "Database error: " .
                                    $stmt->error;

                                $stmt->close();
                            }
                        }
                    }
                }
            }
        }
    }
}


/* ==============================
   GET PORTFOLIO
============================== */

$stmt = $conn->prepare("
    SELECT
        id,
        image,
        title,
        description,
        created_at
    FROM provider_portfolio
    WHERE provider_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $provider_id);
$stmt->execute();

$portfolio = $stmt->get_result();

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Work | KaziHub Tanzania</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Arial,
        sans-serif;

    background: #f5f7fb;
    color: #172033;
}


/* =========================
   HEADER
========================= */

.header {
    background:
        linear-gradient(
            135deg,
            #0f172a,
            #2563eb
        );

    color: white;

    padding: 28px 20px;
}

.header-content {
    max-width: 1100px;

    margin: auto;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;
}

.header h1 {
    margin: 0;

    font-size: 28px;
}

.header p {
    margin: 7px 0 0;

    opacity: .85;
}

.back-btn {
    color: white;

    text-decoration: none;

    background:
        rgba(255,255,255,.15);

    padding: 11px 17px;

    border-radius: 10px;

    transition: .2s;
}

.back-btn:hover {
    background:
        rgba(255,255,255,.25);
}


/* =========================
   CONTAINER
========================= */

.container {
    max-width: 1100px;

    margin: 30px auto;

    padding: 0 20px;
}


/* =========================
   MESSAGES
========================= */

.message {
    padding: 15px 18px;

    border-radius: 12px;

    margin-bottom: 20px;

    background: #fee2e2;

    color: #991b1b;
}

.success {
    background: #dcfce7;

    color: #166534;
}


/* =========================
   UPLOAD BOX
========================= */

.upload-box {
    background: white;

    padding: 28px;

    border-radius: 20px;

    box-shadow:
        0 8px 30px rgba(0,0,0,.07);

    margin-bottom: 38px;
}

.upload-box h2 {
    margin-top: 0;
}

.upload-box p {
    color: #64748b;

    line-height: 1.6;
}


/* =========================
   FORM
========================= */

.form-group {
    margin-bottom: 19px;
}

.form-group label {
    display: block;

    font-weight: 700;

    margin-bottom: 8px;
}

.form-group input,
.form-group textarea {

    width: 100%;

    padding: 14px;

    border:
        1px solid #dbe1ea;

    border-radius: 11px;

    font-size: 15px;

    outline: none;
}

.form-group input:focus,
.form-group textarea:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(37,99,235,.10);
}

.form-group textarea {

    min-height: 110px;

    resize: vertical;
}


/* =========================
   FILE INPUT
========================= */

.form-group input[type="file"] {

    background: #f8fafc;

    cursor: pointer;
}


/* =========================
   BUTTON
========================= */

.upload-btn {

    border: none;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color: white;

    padding: 14px 22px;

    border-radius: 11px;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s;
}

.upload-btn:hover {

    transform: translateY(-1px);

    box-shadow:
        0 8px 20px rgba(37,99,235,.25);
}


/* =========================
   GALLERY
========================= */

.gallery {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 22px;

    margin-top: 20px;
}


/* =========================
   CARD
========================= */

.card {

    background: white;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 8px 25px rgba(0,0,0,.07);

    transition: .25s;
}

.card:hover {

    transform: translateY(-4px);

    box-shadow:
        0 15px 35px rgba(0,0,0,.11);
}


/* =========================
   IMAGE
========================= */

.card img {

    width: 100%;

    height: 230px;

    object-fit: cover;

    display: block;
}


/* =========================
   CARD CONTENT
========================= */

.card-content {

    padding: 18px;
}

.card h3 {

    margin:
        0 0 9px;

    font-size: 19px;
}

.card p {

    color: #64748b;

    line-height: 1.55;
}


/* =========================
   DELETE
========================= */

.delete-btn {

    display: inline-block;

    text-decoration: none;

    color: #dc2626;

    background: #fee2e2;

    padding: 9px 13px;

    border-radius: 8px;

    font-size: 14px;

    font-weight: 600;

    margin-top: 8px;
}

.delete-btn:hover {

    background: #fecaca;
}


/* =========================
   EMPTY
========================= */

.empty {

    background: white;

    padding: 55px 20px;

    text-align: center;

    border-radius: 18px;

    color: #64748b;

    box-shadow:
        0 8px 25px rgba(0,0,0,.05);
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:800px) {

    .gallery {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media(max-width:550px) {

    .header-content {

        flex-direction: column;

        align-items: flex-start;
    }

    .header h1 {

        font-size: 24px;
    }

    .gallery {

        grid-template-columns: 1fr;
    }

    .upload-box {

        padding: 20px;
    }
}

</style>

</head>


<body>


<header class="header">

<div class="header-content">

<div>

<h1>📸 My Work</h1>

<p>
Showcase your professional work to customers
</p>

</div>


<a
    href="provider-dashboard.php"
    class="back-btn"
>
    ← Dashboard
</a>

</div>

</header>


<div class="container">


<?php if ($error !== ""): ?>

<div class="message">

⚠️ <?= $error ?>

</div>

<?php endif; ?>


<?php if (isset($_GET["uploaded"])): ?>

<div class="message success">

✅ Kazi yako imeongezwa successfully!

</div>

<?php endif; ?>


<?php if (isset($_GET["deleted"])): ?>

<div class="message success">

🗑️ Kazi imefutwa successfully.

</div>

<?php endif; ?>


<!-- =========================
     UPLOAD FORM
========================= -->

<div class="upload-box">

<h2>
➕ Add Your Work
</h2>

<p>
Upload picha za kazi zako ili customers
waweze kuziona kwenye profile yako.
</p>


<form
    method="POST"
    enctype="multipart/form-data"
>


<div class="form-group">

<label>
Work Title
</label>

<input
    type="text"
    name="title"
    placeholder="Mfano: House Painting"
    required
>

</div>


<div class="form-group">

<label>
Description
</label>

<textarea
    name="description"
    placeholder="Elezea kidogo kuhusu kazi hii..."
></textarea>

</div>


<div class="form-group">

<label>
Work Image
</label>

<input
    type="file"
    name="work_image"
    accept=".jpg,.jpeg,.png,.webp"
    required
>

</div>


<button
    type="submit"
    class="upload-btn"
>
    📤 Upload Work
</button>


</form>

</div>


<!-- =========================
     PORTFOLIO
========================= -->

<h2>
My Portfolio
</h2>


<?php if ($portfolio->num_rows > 0): ?>


<div class="gallery">


<?php while ($work = $portfolio->fetch_assoc()): ?>


<div class="card">


<img
    src="<?= htmlspecialchars(
        $upload_url . $work["image"]
    ) ?>"
    alt="<?= htmlspecialchars(
        $work["title"]
    ) ?>"
>


<div class="card-content">


<h3>
<?= htmlspecialchars(
    $work["title"]
) ?>
</h3>


<?php if (!empty($work["description"])): ?>

<p>
<?= nl2br(
    htmlspecialchars(
        $work["description"]
    )
) ?>
</p>

<?php endif; ?>


<a
    href="provider-portfolio.php?delete=<?= (int)$work["id"] ?>"
    class="delete-btn"
    onclick="return confirm('Una uhakika unataka kufuta kazi hii?');"
>
    🗑️ Delete
</a>


</div>

</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<div class="empty">

<h2>
📸 No Work Yet
</h2>

<p>
Upload picha yako ya kwanza ya kazi.
</p>

</div>


<?php endif; ?>


</div>


</body>

</html>