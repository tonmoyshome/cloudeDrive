<?php
session_start();

// Create a test session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session created for user ID: " . $_SESSION['user_id'] . "<br>";
echo "Testing direct download API call...<br>";
echo '<a href="api/download.php?file_id=1409" target="_blank">Download File 1409</a><br>';
echo '<script>
    async function testDownload() {
        try {
            const response = await fetch("api/download.php?file_id=1409");
            console.log("Response status:", response.status);
            console.log("Response headers:", [...response.headers.entries()]);
            
            if (response.ok) {
                const blob = await response.blob();
                console.log("Blob size:", blob.size);
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "test_download.md";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                document.getElementById("result").innerHTML = "Download initiated successfully!";
            } else {
                const text = await response.text();
                document.getElementById("result").innerHTML = "Download failed: " + text;
            }
        } catch (error) {
            console.error("Download error:", error);
            document.getElementById("result").innerHTML = "Download error: " + error.message;
        }
    }
</script>
<button onclick="testDownload()">Test Download with JavaScript</button>
<div id="result"></div>';
?>
