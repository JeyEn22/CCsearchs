<?php
if(extension_loaded('imagick')) {
    echo "Imagick is installed (informational). This app uses an external PDF→image API by default.";
} else {
    echo "Imagick not loaded (ok). The app will use an external API for PDF previews.";
}
?>
