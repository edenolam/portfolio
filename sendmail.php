<?php
// On garde l'affichage des erreurs pour tes tests Hostinger
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifie bien que le dossier se nomme exactement PHPMailer-master sur ton FTP
require __DIR__ . '/phpmailer/PHPMailer-master/src/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/phpmailer/PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Protection Honeypot
    if (!empty($_POST['website'])) {
        header("Location: index.php?status=spam#contact");
        exit;
    }

    // 2. Récupération des données (Synchronisées avec ton HTML)
    $nom     = htmlspecialchars(trim($_POST["name"] ?? ''));    // name="name"
    $email   = filter_var(trim($_POST["email"] ?? ''), FILTER_VALIDATE_EMAIL); // name="email"
    $sujet   = htmlspecialchars(trim($_POST["subject"] ?? '')); // name="subject"
    $message = htmlspecialchars(trim($_POST["message"] ?? '')); // name="message"

    // 3. Validation stricte
    if (!$nom || !$email || !$sujet || !$message) {
        header("Location: index.php?status=error#contact");
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Configuration SMTP Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contact@edenolam.com';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;

        // Expéditeur et Destinataire
        $mail->setFrom('contact@edenolam.com', 'Portfolio EdenOlam');
        $mail->addAddress('julienbasquin@hotmail.com');
        $mail->addReplyTo($email, $nom); // Permet de répondre direct au client

        // Contenu du mail
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true); // Mail en HTML pour plus de clarté
        $mail->Subject = "[Portfolio] Nouveau message : $sujet";

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; border: 1px solid #eee; padding: 20px;'>
                <h2 style='color: #333;'>Nouveau contact client</h2>
                <p><strong>Nom :</strong> $nom</p>
                <p><strong>Email :</strong> $email</p>
                <p><strong>Sujet :</strong> $sujet</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p><strong>Message :</strong></p>
                <p style='white-space: pre-wrap;'>$message</p>
            </div>";

        $mail->AltBody = "Nom : $nom\nEmail : $email\nSujet : $sujet\n\nMessage :\n$message";

        $mail->send();

        // 4. Succès : Redirection vers index.php
        header("Location: index.php?status=success#contact");
        exit;

    } catch (Exception $e) {
        // Log l'erreur réelle dans le fichier error_log du serveur
        error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        header("Location: index.php?status=error#contact");
        exit;
    }

} else {
    // Si quelqu'un tente d'accéder au script directement
    http_response_code(405);
    header("Location: index.php");
    exit;
}