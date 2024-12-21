<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errorTitle = isset($title) ? $title : '404 Error';
$errorMessage = isset($message) ? $message : 'Page Not Found';

// Get correct path for image
$imagePath = 'http://localhost/cashbalance/assets/404.png';
?>
<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* बैकग्राउंड इमेज स्टाइल */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('<?php echo $imagePath; ?>');
            background-size: cover;
            background-position: center;
            opacity: 0.1; /* बैकग्राउंड की ट्रांसपेरेंसी */
            z-index: -1;
        }

        .error-page {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .error-image {
            max-width: 35%; /* छोटी इमेज साइज */
        }

        .error-message {
            font-size: 24px;
            color: #343a40;
            margin: 20px 0;
        }

        .back-button {
            background: #007bff;
            color: white;
            padding: 10px 30px;
            border-radius: 30px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: #0056b3;
            color: white;
            transform: translateY(-2px);
        }

        /* एनिमेशन फॉर मेन इमेज */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .error-image {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-page">
            <img src="<?php echo $imagePath; ?>" alt="Error" class="error-image">
            <h2 class="error-message"><?php echo $errorMessage; ?></h2>
            <p class="text-muted">कृपया बाद में पुनः प्रयास करें</p>
            <a href="javascript:history.back()" class="back-button">
                <i class="fas fa-arrow-left"></i> वापस जाएं
            </a>
        </div>
    </div>
</body>

</html>