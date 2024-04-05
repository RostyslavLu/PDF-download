<?php
/*
Plugin Name: PDF Downloader for Famaliving Montreal
Description: A plugin to download PDF files from famasofas.com and upload them to the media library. The plugin also displays a list of downloaded files with links to the media library.
Version: 1.1
Author: Rostyslav Luchyshyn
*/

// Add custom CSS to the plugin page
function custom_pdf_downloader_styles()
{
    wp_enqueue_style('custom-pdf-downloader-styles', plugin_dir_url(__FILE__) . 'assets/css/main.css');
}
add_action('admin_enqueue_scripts', 'custom_pdf_downloader_styles');

// Add menu item in dashboard
function custom_pdf_downloader_menu()
{
    add_menu_page(
        'Custom PDF Downloader',
        'PDF Downloader',
        'manage_options',
        'custom-pdf-downloader',
        'custom_pdf_downloader_page',
        'dashicons-download',
        6
    );
}
add_action('admin_menu', 'custom_pdf_downloader_menu');

// Plugin page content for downloading PDFs from a specified page URL
function custom_pdf_downloader_page()
{
?>
    <div class="container-form-download">
        <h1>Custom PDF Downloader for Famaliving Montreal</h1>
        <form method="post">
            <label for="page_url">Enter Page URL:</label>
            <input type="text" id="page_url" name="page_url" size="50" placeholder="ex. https://famasofas.com/product-name">
            <input class="btn-custom-download" type="submit" name="submit" value="Download PDFs">
        </form>
    </div>
    <?php
    // Call download_pdfs() function here to display results after the form content
    download_pdfs();
    ?>
    <?php
}

// Function to download PDFs from the provided page URL
function download_pdfs()
{
    if (isset($_POST['submit'])) {
        $page_url = esc_url($_POST['page_url']);

        if (!filter_var($page_url, FILTER_VALIDATE_URL)) {
            echo "<p class='error-url'>Invalid URL</p>";
            return;
        }

        // Fetch page content
        $html = file_get_contents($page_url);

        // Check if page content was fetched successfully
        if ($html === false) {
            echo "<p>Failed to fetch page content</p>";
            return;
        }

        // Create a DOMDocument object
        $dom = new DOMDocument();
        // Suppress errors when loading HTML content
        libxml_use_internal_errors(true);
        // Load HTML content from the page
        $dom->loadHTML($html);
        libxml_clear_errors();
        // Get all links from the page
        $links = $dom->getElementsByTagName('a');

        // Extract h1 text
        $header_box = $dom->getElementsByTagName('h1')->item(0);
        $h1_text = trim($header_box->nodeValue);

    ?>
        <div class="content-item-code">
            <?php
            $h1_content = "Download the technical documentation: schematics, measurements, technical details, brochures, and manuals.";
            echo esc_html($h1_content);
            echo "<br>";
            echo esc_html("<ul>");
            echo "<br>";
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                $title = $link->nodeValue;

                if (pathinfo($href, PATHINFO_EXTENSION) == 'pdf') {
                    // Replace spaces with %20
                    $file_url = 'https://famasofas.com' . str_replace(' ', '%20', $href);
                    // Append -Famaliving-Montreal to the file name
                    $file_name = $title . '-famaliving-Montreal.pdf';
                    try {
                        // Check if the file exists
                        $file_data = @file_get_contents($file_url);
                        if ($file_data !== false) {
                            // Save file to uploads directory
                            $upload = wp_upload_bits($file_name, null, $file_data);
                            if (!$upload['error']) {
                                // Insert file into media library
                                $file_path = $upload['file'];
                                $file_type = wp_check_filetype($file_name, null);
                                $attachment = array(
                                    'post_mime_type' => $file_type['type'],
                                    'post_title' => sanitize_file_name($file_name),
                                    'post_content' => '',
                                    'post_status' => 'inherit'
                                );
                                $attachment_id = wp_insert_attachment($attachment, $file_path);
                                if (!is_wp_error($attachment_id)) {
                                    require_once ABSPATH . 'wp-admin/includes/image.php';
                                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                                    $el_li = "<li><a href='" . esc_url(wp_get_attachment_url($attachment_id)) . "' target='_blank' rel='noopener noreferrer'>$title</a></li>";
                                    echo esc_html($el_li);
                                    echo "<br>";
                                } else {
                                    echo "<!-- Error inserting file '$file_name' into media library: " . $attachment_id->get_error_message() . " -->";
                                }
                            } else {
                                echo "<!-- Error uploading file '$file_name': " . $upload['error'] . " -->";
                            }
                        } else {
                            throw new Exception("Failed to download file '$file_name'");
                        }
                    } catch (Exception $e) {
                        // Output a message indicating the failure to download the file
                        echo "<!-- Error: " . $e->getMessage() . " -->";
                        // Skip this file and continue with the loop
                        continue;
                    }
                }
            }
            echo esc_html("</ul>");
            echo "<br>";
            $btn3D = "<strong>Explore $h1_text configurability with our <a href='/fama-3d-sim/' target='_blank' rel='noopener'>3D Design Simulation&gt;&gt;</a></strong>";
            echo esc_html($btn3D);
            ?>
        </div>
        <?php
        ?>
        <button class="btn-copy-text-code" id="copyButton">Copy Text</button>
        <button class="btn-new-download" onclick="window.location.reload();">New Download</button>
        <!-- Copy the content of the .content-item-code div to the clipboard -->
        <script>
            document.getElementById("copyButton").addEventListener("click", async function() {
                const textToCopy = document.querySelector(".content-item-code").innerText;

                try {
                    await navigator.clipboard.writeText(textToCopy);
                    //alert("Block copied to clipboard");
                    document.getElementById("copyButton").textContent = "Copied!";
                    document.getElementById("copyButton").style.backgroundColor = "var(--color-secondary)";
                    setTimeout(() => {
                        document.getElementById("copyButton").textContent = "Copy Text";
                        document.getElementById("copyButton").style.backgroundColor = "var(--color-primary)";
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy: ', err);
                    //alert("Failed to copy block to clipboard");
                    document.getElementById("copyButton").textContent = "Failed to copy";
                    document.getElementById("copyButton").style.backgroundColor = "var(--color-error)";
                }
            });

        </script>

<?php
    }
}

?>